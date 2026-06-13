<?php

declare(strict_types=1);

namespace tests\Exhaust\Routing;

use Exhaust\Routing\Route;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    private function makeRoute(): Route
    {
        return new Route();
    }

    // --- registerGetRoute ---

    public function test_registerGetRoute_adds_route_to_GET(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/users', ['App\Controllers\UserController', 'index'])->name('users.index');

        $routes = $route->all('GET');
        $this->assertArrayHasKey('users.index', $routes);
        $this->assertEquals('/users', $routes['users.index']['path']);
    }

    // --- registerPostRoute ---

    public function test_registerPostRoute_adds_route_to_POST(): void
    {
        $route = $this->makeRoute();
        $route->registerPostRoute('/users', ['App\Controllers\UserController', 'store'])->name('users.store');

        $routes = $route->all('POST');
        $this->assertArrayHasKey('users.store', $routes);
        $this->assertEquals('/users', $routes['users.store']['path']);
    }

    // --- registerPutRoute ---

    public function test_registerPutRoute_adds_route_to_PUT(): void
    {
        $route = $this->makeRoute();
        $route->registerPutRoute('/users/{id}', ['App\Controllers\UserController', 'update'])->name('users.update');

        $routes = $route->all('PUT');
        $this->assertArrayHasKey('users.update', $routes);
    }

    // --- registerDeleteRoute ---

    public function test_registerDeleteRoute_adds_route_to_DELETE(): void
    {
        $route = $this->makeRoute();
        $route->registerDeleteRoute('/users/{id}', ['App\Controllers\UserController', 'destroy'])->name('users.destroy');

        $routes = $route->all('DELETE');
        $this->assertArrayHasKey('users.destroy', $routes);
    }

    // --- name() chaining ---

    public function test_name_renames_route(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/home', ['HomeController', 'index'])->name('home');

        $routes = $route->all('GET');
        $this->assertArrayHasKey('home', $routes);
        $this->assertEquals('/home', $routes['home']['path']);
    }

    public function test_name_is_fluent(): void
    {
        $route = $this->makeRoute();
        $result = $route->registerGetRoute('/test', ['Ctrl', 'fn'])->name('test.route');
        $this->assertInstanceOf(Route::class, $result);
    }

    // --- middlewares() ---

    public function test_middlewares_attaches_to_current_route(): void
    {
        $route = $this->makeRoute();
        $route
            ->registerGetRoute('/admin', ['AdminController', 'index'])
            ->name('admin.index')
            ->middlewares(['AuthMiddleware', 'AdminMiddleware']);

        $routes = $route->all('GET');
        $this->assertArrayHasKey('middlewares', $routes['admin.index']);
        $this->assertContains('AuthMiddleware', $routes['admin.index']['middlewares']);
    }

    public function test_middlewares_is_fluent(): void
    {
        $route = $this->makeRoute();
        $result = $route
            ->registerGetRoute('/secure', ['Ctrl', 'fn'])
            ->name('secure')
            ->middlewares(['Auth']);
        $this->assertInstanceOf(Route::class, $result);
    }

    // --- all() ---

    public function test_all_without_method_returns_all_methods(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/a', ['C', 'fn'])->name('a');
        $route->registerPostRoute('/b', ['C', 'fn'])->name('b');

        $all = $route->all();
        $this->assertArrayHasKey('GET', $all);
        $this->assertArrayHasKey('POST', $all);
        $this->assertArrayHasKey('PUT', $all);
        $this->assertArrayHasKey('DELETE', $all);
    }

    public function test_all_with_method_returns_only_that_method(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/only-get', ['C', 'fn'])->name('only.get');

        $gets = $route->all('get'); // lowercase should still work via strtoupper
        $this->assertArrayHasKey('only.get', $gets);
    }

    // --- single action controller (no function key) ---

    public function test_single_action_controller_has_null_function(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/', ['HomeInvokable'])->name('home');

        $routes = $route->all('GET');
        $this->assertNull($routes['home']['function']);
    }

    // --- class key ---

    public function test_route_stores_class_name(): void
    {
        $route = $this->makeRoute();
        $route->registerGetRoute('/profile', ['App\Controllers\ProfileController', 'show'])->name('profile');

        $routes = $route->all('GET');
        $this->assertEquals('App\Controllers\ProfileController', $routes['profile']['class']);
    }
}
