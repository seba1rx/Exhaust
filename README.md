# Exhaust

Minimalist PHP framework built around a **commands-based response pattern** for single-page applications. The primary mode of interaction is a set of typed commands that the frontend engine (`engeen.js`) interprets and executes. The framework also supports pure API responses for clients that consume JSON directly, without `engeen.js`.

---

## Core Philosophy

**SPA mode** — the primary use case:

```
Browser  ──XHR/Fetch──▶  PHP Controller  ──Commands JSON──▶  engeen.js
```

When the user interacts with the SPA, the frontend sends an asynchronous request (XHR or Fetch) to a PHP controller. The controller builds a response by calling static methods on `Commands`, which queue up instructions. At the end of the request cycle the framework serialises those instructions as JSON and `engeen.js` executes them in order (FIFO).

**API mode** — when the backend is consumed as a plain REST API:

```
Client  ──HTTP──▶  PHP Controller  ──Plain JSON──▶  Client
```

The `Commands::apiResponse()` method sends a plain JSON object not intended for `engeen.js`. Use this when the controller serves a mobile app, a third-party client, or any consumer that reads JSON directly without the SPA engine.

---

## Installation

```bash
composer require seba1rx/exhaust
```

---

## Project structure

```
your-app/
├── public/index.php            ← single entry point
├── public/assets/js/
│   ├── engeen.js               ← frontend engine
│   └── drivers/                ← dialog/toast drivers (SweetAlert2, Notiflix, …)
├── App/
│   ├── Controllers/
│   │   └── Controller.php      ← app-level base controller (handles middlewares)
│   ├── Middlewares/
│   └── Models/
├── config/
│   ├── config.php              ← global configuration
│   └── routes.php              ← route definitions
└── resources/templates/        ← views
```

The package (`seba1rx/exhaust`) provides the `Exhaust\` namespace. Your application code lives in the `App\` namespace. Controllers, middlewares and models are app concerns — the package does not define a controller base class.

---

## Routing

Routes are defined in `config/routes.php` using the `Route` object.

```php
use App\Controllers\Wellcome;
use App\Controllers\UserController;

$routes = new Route();

$routes->registerGetRoute('/', [Wellcome::class, 'showLandingPage'])->name('home');

$routes->registerPostRoute('/users', [UserController::class, 'store'])
       ->name('users.store')
       ->middlewares(['Authentication']);

$routes->registerGetRoute('/users/{id}', [UserController::class, 'show'])
       ->name('users.show');

$routes->registerPutRoute('/users/{id}', [UserController::class, 'update']);
$routes->registerDeleteRoute('/users/{id}', [UserController::class, 'destroy']);

// Single-action controller — no method, class must be invokable
$routes->registerDeleteRoute('/session', [\App\Controllers\Logout::class]);
```

| Method | Helper |
|--------|--------|
| GET | `registerGetRoute($path, $action)` |
| POST | `registerPostRoute($path, $action)` |
| PUT | `registerPutRoute($path, $action)` |
| DELETE | `registerDeleteRoute($path, $action)` |

Route parameters wrapped in `{braces}` are captured and type-cast automatically (int, float, bool, string).

---

## Controllers

Controllers live in `App\Controllers\` and extend your app-level `Controller` base class (which handles middleware execution). Each action receives the request payload as an associative array and **must return `Commands::all()`**.

```php
namespace App\Controllers;

use App\Controllers\Controller;
use Exhaust\Response\Commands;

class UserController extends Controller
{
    public function edit(array $payload): array
    {
        $html = app()->render('user/edit.html.twig', ['id' => $payload['id']]);

        Commands::html('content-section', $html);

        return Commands::all();
    }

    public function destroy(array $payload): array
    {
        // … delete logic …

        Commands::dialog(type: 'success', options: Commands::dialogBuilder(
            icon: 'success',
            title: 'Deleted',
            btn_confirm: ['text' => 'Ok', 'callback' => "Engeen.route('/users')"],
        ));

        return Commands::all();
    }
}
```

The app-level `Controller` base constructor handles middlewares:

```php
namespace App\Controllers;

class Controller
{
    public function __construct(array $middlewares)
    {
        $this->invokeBeforeMiddlewares($middlewares, app()->request->payload);
    }
    // …
}
```

---

## Commands reference

All methods are static on `Exhaust\Response\Commands`. Commands accumulate in a static array and are flushed at the end of each request by `app()->run()`.

---

### `html` — inject HTML into a DOM element

```php
Commands::html('content-section', '<p>New content</p>');
Commands::html('sidebar', app()->render('partials/sidebar.twig', $data));
```

Multiple calls are applied in order. The frontend does `document.getElementById(id).innerHTML = content`.

---

### `script` — run arbitrary JavaScript

The snippet is minified automatically before sending.

```php
Commands::script("myApp.loadSection('profile'); myApp.highlightMenu('users');");
```

---

### `assignValue` — set a JavaScript variable

```php
Commands::assignValue('currentUserId', 42);
Commands::assignValue('config', ['theme' => 'dark', 'lang' => 'es']);
```

---

### `dialog` + `dialogBuilder` — SweetAlert2 modal

```php
// Recommended: use dialogBuilder for readable named parameters
Commands::dialog(type: 'success', options: Commands::dialogBuilder(
    icon: 'success',
    title: 'Saved',
    text: 'Your changes have been saved.',
    btn_confirm: ['text' => 'Ok', 'callback' => 'Engeen.route("/dashboard")'],
));

Commands::dialog(type: 'warning', options: Commands::dialogBuilder(
    icon: 'warning',
    title: 'Are you sure?',
    btn_confirm: ['text' => 'Yes, delete', 'class' => 'btn-danger', 'callback' => 'deleteItem()'],
    btn_cancel:  ['text' => 'Cancel'],
));

Commands::dialog(type: 'info', options: Commands::dialogBuilder(
    icon: 'info',
    title: 'Session expiring',
    timer: ['time' => 5000, 'callback' => 'logout()'],
    showLoading: true,
));
```

**`dialogBuilder` parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `icon` | string | `success` `error` `info` `warning` `question` |
| `title` | string\|null | Large heading text |
| `text` | string\|null | Body message |
| `html` | string\|null | HTML body — overrides `text`, minified automatically |
| `btn_confirm` | array\|null | `{text, ?class, ?callback}` |
| `btn_deny` | array\|null | `{text, ?class, ?callback}` |
| `btn_cancel` | array\|null | `{text, ?class, ?callback}` |
| `timer` | array\|null | `{?time: int (ms, multiple of 1000), ?callback}` |
| `showLoading` | string\|true\|null | Show a loading indicator inside the dialog |

---

### `toast` — SweetAlert2 toast notification

```php
Commands::toast('success', [
    'title'    => 'Changes saved',
    'duration' => 3000,
    'position' => 'top-end',
]);

Commands::toast('error', ['title' => 'Something went wrong', 'duration' => 5000]);
```

**Allowed types:** `success` `error` `info` `warning` `question` `any`

**Allowed positions:** `top` `top-start` `top-end` `center` `center-start` `center-end` `bottom` `bottom-start` `bottom-end`

---

### `log` — typed browser console output

Sends a colour-coded log entry to the browser console. Overrides the framework's `debug_request` config for this response.

```php
Commands::log(type: 'info',    text: 'User created',   details: ['id' => 5]);
Commands::log(type: 'warning', text: 'Slow query',      details: ['ms' => 820]);
Commands::log(type: 'error',   text: 'Payment failed');
Commands::log(type: 'debug',   details: $payload);
```

**Allowed types:** `info` `error` `debug` `warning`

---

### `console_log` — raw `console.log`

```php
Commands::console_log('checkpoint reached');
Commands::console_log(['key' => 'value', 'count' => 3]);
```

---

### `echo` — send a full HTML page

Used for navigation requests. The content bypasses the commands engine and is rendered directly as HTML.

```php
Commands::echo(html: app()->render('landing/landing.html.twig'));
```

---

### `apiResponse` — plain JSON response (API mode)

Sends a plain JSON object. The response is **not processed by `engeen.js`** — it is intended for clients that consume the backend as a REST API (mobile apps, third-party integrations, etc.). Cannot be combined with other commands in the same response.

```php
Commands::apiResponse(['users' => $list, 'total' => count($list)]);
```

---

### `includeScript` / `includeCss` — inject assets dynamically

Place these **before** any `html` or `script` command that depends on the file.

```php
Commands::includeScript('/assets/js/chart.min.js');
Commands::script('renderChart(' . json_encode($data) . ')');
```

---

## A complete controller action

```php
public function saveProfile(array $payload): array
{
    $bio = $payload['bio'] ?? '';

    if (empty($bio)) {
        Commands::dialog(type: 'warning', options: Commands::dialogBuilder(
            icon: 'warning',
            title: 'Validation error',
            text: 'Bio cannot be empty.',
        ));
        return Commands::all();
    }

    // persist …
    app()->dbLink->update(
        'UPDATE users SET bio = :bio WHERE id = :id',
        [':bio' => $bio, ':id' => $payload['userId']]
    );

    Commands::html('profile-bio', htmlspecialchars($bio));
    Commands::toast('success', ['title' => 'Profile updated', 'duration' => 3000, 'position' => 'top-end']);
    Commands::log(type: 'info', text: 'Profile saved', details: ['userId' => $payload['userId']]);

    return Commands::all();
}
```

The JSON sent to the frontend:

```json
{
  "html":  { "profile-bio": "New bio text here" },
  "toast": { "success": { "title": "Profile updated", "duration": 3000, "position": "top-end" } },
  "log":   { "info": { "text": "Profile saved", "details": { "userId": 7 } } }
}
```

---

## Frontend — engeen.js

The frontend counterpart of the framework. Exposes a global `Engeen` object with no external dependencies (dialogs and toasts are delegated to a registered driver).

### Setup

```html
<!-- 1. dialog library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- 2. engeen core -->
<script src="/assets/js/engeen.js"></script>

<!-- 3. driver -->
<script src="/assets/js/drivers/engeen-swal2-driver.js"></script>

<!-- 4. register driver -->
<script>Engeen.setDialogDriver(EngeenSwal2Driver);</script>
```

### Sending requests

```js
Engeen.request.post({ url: '/users', payload: { name: 'Alice' }, showLoading: true });
Engeen.request.get({ url: '/users', payload: { page: 2 } });
Engeen.request.put({ url: '/users/7', payload: { bio: 'Hello' } });
Engeen.request.delete({ url: '/users/7', payload: { id: 7 } });
```

**Request options**

| Option | Description |
|--------|-------------|
| `url` | Target route (required) |
| `payload` | Data to send as request body |
| `showLoading` | Show a loading dialog — `true` or a string title |
| `before_script` | JS evaluated before sending |
| `done_script` | JS evaluated after the response is processed |

`fetch()` requests must include `X-Requested-With: fetch` so the backend detects `RequestType::Fetch`. `engeen.js` adds this header automatically.

### How commands are processed

`Engeen.executeCommands(response)` iterates the JSON received from the server:

| JSON key | Browser effect |
|----------|---------------|
| `html` | `document.getElementById(id).innerHTML = content` |
| `script` | `eval(script)` |
| `console_log` | `console.log(value)` |
| `log.info/error/debug/warning` | Colour-coded console output |
| `dialog` | Delegated to the registered driver |
| `toast` | Delegated to the registered driver |
| `assignValue` | Assigns a global JS variable via `eval()` |

### Triggering dialogs and toasts directly from JS

```js
Engeen.popDialog.success({ title: 'Saved', text: 'All good.', buttons: { confirm: { text: 'Ok' } } });
Engeen.popDialog.error({ text: 'Something went wrong' });
Engeen.popDialog.any({ loading: true, title: 'Processing…' });

Engeen.popToast.success({ title: 'Done', duration: 3000, position: 'top-end' });
Engeen.popToast.warning({ title: 'Watch out' });
```

### Utilities

```js
Engeen.route('/dashboard');         // navigate — window.location.replace
Engeen.redirect('https://…');      // alias

Engeen.console.info('msg', obj);
Engeen.console.error('msg', obj);
Engeen.console.debug('msg', obj);
Engeen.console.warning('msg', obj);

Engeen.form.getData('form-id');     // FormData → plain object

Engeen.tab.id;                      // unique UUID for this browser tab
```

---

## Request object

Available via `app()->request` inside any controller.

```php
app()->request->payload               // stdClass — all body params, type-cast automatically
app()->request->uri->path             // '/users/7'
app()->request->getRequestMethod()    // 'POST'
app()->request->isAsync()             // true for XHR and Fetch
app()->request->isNavigation()        // true for regular browser navigation
app()->request->getRemoteAddress()
app()->request->getUriParam('page')   // query string: ?page=2
app()->request->hasUpload()
```

Payload values are automatically cast to their detected type (int, float, bool, string).

---

## PSR interoperability

The package ships three contracts in `Exhaust\Contracts\` that align the framework with PSR standards without changing the existing workflow.

---

### `RequestBlueprint` — typed request contract

`Request` now implements `RequestBlueprint` (which extends `UrlBlueprint`). Type-hint against the contract wherever you want static-analysis tools and IDEs to understand the full API of the request object.

```php
use Exhaust\Contracts\RequestBlueprint;

// Type-hinting in a service or utility that receives the request
public function audit(RequestBlueprint $request): void
{
    $method  = $request->getRequestMethod(); // 'POST'
    $ip      = $request->getRemoteAddress(); // '192.168.1.10'
    $payload = $request->payloadAsArray();   // ['userId' => 7, 'action' => 'delete']
    $isXHR   = $request->isAsync();          // true
}
```

---

### `Request::toPsr7()` — PSR-7 adapter

Converts the framework request into a `Psr\Http\Message\ServerRequestInterface` on demand, for use with PSR-15 middleware or any library that expects a PSR-7 object.

Requires a PSR-17 `ServerRequestFactoryInterface` implementation — install any compliant library in your app:

```bash
composer require nyholm/psr7
```

```php
use Nyholm\Psr7\Factory\Psr17Factory;

$factory    = new Psr17Factory();
$psr7       = app()->request->toPsr7($factory);

// Pass to any PSR-15 middleware or PSR-7-aware library
$psr7       = $psr7->withAttribute('user', $currentUser);
$response   = $someExternalMiddleware->process($psr7, $handler);
```

The adapter maps:

| Framework request | PSR-7 ServerRequest |
|---|---|
| `$request->method` | `getMethod()` |
| `$request->uri->string` | `getUri()` |
| `$_SERVER['HTTP_*']` | `getHeaders()` |
| `$request->payloadAsArray()` | `getParsedBody()` |
| `$request->uri->query` (parsed) | `getQueryParams()` |
| `$_COOKIE` | `getCookieParams()` |
| `$_SERVER` | `getServerParams()` |

---

### `MiddlewareBlueprint` — PSR-15 middlewares

`MiddlewareBlueprint` extends PSR-15 `MiddlewareInterface`. Implementing it gives:
- interoperability with third-party PSR-15 libraries (CORS, rate-limiting, JWT auth, etc.)
- explicit, type-safe contract over the legacy invokable pattern
- IDE completion and static-analysis support

**PSR-15 middleware (recommended for new middlewares):**

```php
namespace App\Middlewares;

use Exhaust\Contracts\MiddlewareBlueprint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;

final class AuthMiddleware implements MiddlewareBlueprint
{
    /**
     * Validates the session and either short-circuits with a redirect or
     * delegates to the next handler in the pipeline.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface
    {
        if (!isset($_SESSION['user'])) {
            // Short-circuit: return a redirect without reaching the controller
            return (new Response(302))->withHeader('Location', '/login');
        }

        // Delegate to the next middleware or the controller
        return $handler->handle($request);
    }
}
```

**Legacy invokable middleware (still fully supported):**

```php
namespace App\Middlewares;

final class AuthMiddleware
{
    /** Redirects to login if no active session exists. */
    public function __invoke(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

**Updating `App\Controllers\Controller` to support both patterns:**

The app-level `Controller` base detects whether each middleware implements `MiddlewareBlueprint` and dispatches accordingly. Middlewares that don't implement the interface continue to work as invokables.

```php
namespace App\Controllers;

use Exhaust\Contracts\MiddlewareBlueprint;
use Nyholm\Psr7\Factory\Psr17Factory;

class Controller
{
    public function __construct(array $middlewares)
    {
        $this->invokeBeforeMiddlewares($middlewares, app()->request->payload);
    }

    /**
     * Executes each middleware before the controller action.
     * Supports both PSR-15 (MiddlewareBlueprint) and legacy invokable middlewares.
     *
     * @param array     $middlewares List of middleware class names.
     * @param \stdClass $payload     Current request payload.
     * @return void
     */
    public function invokeBeforeMiddlewares(
        array $middlewares,
        \stdClass $payload = new \stdClass,
    ): void
    {
        $factory = new Psr17Factory();

        foreach ($middlewares as $className) {
            $middleware = new ("\\App\\Middlewares\\{$className}")();

            if ($middleware instanceof MiddlewareBlueprint) {
                // PSR-15 path
                $psr7    = app()->request->toPsr7($factory);
                $handler = new \App\Http\FinalHandler($factory);
                $middleware->process($psr7, $handler);
            } else {
                // Legacy invokable path
                $middleware($payload);
            }
        }

        if (app()->terminate_session) {
            app()->response = new \stdClass;
        }
    }
}
```

`FinalHandler` is a minimal pass-through that closes the middleware pipeline. Exhaust controllers handle the actual HTTP response through `Commands`, not through PSR-7 response objects.

```php
namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * Terminal handler for the PSR-15 middleware pipeline.
 *
 * Returns an empty 200 response — the actual response body is built by
 * Exhaust's Commands system and sent separately by app()->run().
 */
final class FinalHandler implements RequestHandlerInterface
{
    public function __construct(private readonly Psr17Factory $factory) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->factory->createResponse(200);
    }
}
```

---

### `ResponseBlueprint` — response contract

`ResponseBlueprint` defines the contract for objects that wrap the Commands array and send an HTTP response. Implement it to build a custom response class — useful when you need extra headers, content negotiation, structured error envelopes, or centralised response logging.

```php
namespace App\Http;

use Exhaust\Contracts\ResponseBlueprint;
use Exhaust\Exceptions\LogicException;
use Exhaust\Request\RequestType;
use Exhaust\Tools\CastingTool;

/**
 * Wraps the validated Commands array and serialises it as HTML or JSON
 * depending on the originating request type.
 */
final class Response implements ResponseBlueprint
{
    /**
     * @param array       $commands    Validated command set from Commands::all().
     * @param RequestType $requestType Detected type of the originating request.
     */
    public function __construct(
        private readonly array       $commands,
        private readonly RequestType $requestType,
    ) {}

    /** {@inheritdoc} */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /** {@inheritdoc} */
    public function toJson(): string
    {
        return json_encode(CastingTool::arrayToObject($this->commands));
    }

    /**
     * {@inheritdoc}
     * @throws LogicException When the Commands set has no 'echo' entry.
     */
    public function toHtml(): string
    {
        if (!isset($this->commands['echo'])) {
            throw new LogicException('Response has no echo command — cannot render HTML.');
        }
        return $this->commands['echo'];
    }

    /** {@inheritdoc} */
    public function send(): void
    {
        if ($this->requestType === RequestType::Navigation) {
            header('Content-Type: text/html; charset=UTF-8');
            echo $this->toHtml();
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo $this->toJson();
        }
    }
}
```

Usage at the end of a controller action or inside `app()->run()`:

```php
use Exhaust\Response\Commands;
use App\Http\Response;

// Build commands as usual …
Commands::html('content', $html);
Commands::toast('success', ['title' => 'Saved', 'duration' => 3000]);

// Wrap in the concrete Response and send
$response = new Response(
    commands:    Commands::all(),
    requestType: app()->request->requestType,
);
$response->send();
```

---

## Template engines

Configured in `config/config.php → template_engine.use`. All engines share the same rendering API.

```php
app()->render('folder/template.html.twig', ['user' => $user]);
```

| Config key | Engine |
|------------|--------|
| `twig` | Twig — recommended for production |
| `smarty` | Smarty |
| `piston` | Piston — built-in, PHP-native, no compilation |
| `plates` | Plates |
| `blade` | Blade (via `jenssegers/blade`) |

---

## Running tests

```bash
./vendor/bin/phpunit

# With HTML coverage report (requires Xdebug)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/
```
