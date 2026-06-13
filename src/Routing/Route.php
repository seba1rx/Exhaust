<?php

namespace Exhaust\Routing;

use Exhaust\Tools\StringTool;

class Route
{

    /**
     * Hydrated with the routes defined in Exhaust/routes.php
     * @var array
     */
    private $routes = [
        "GET" => [],
        "POST" => [],
        "PUT" => [],
        "DELETE" => [],
    ];

    /**
     * Placeholder for the name of the route in the process of route
     * identification, if the route does not define a name then the
     * placeholder value is used to name that route.
     *
     * This property is used to identify the route that is being defined
     * by the chained methods until the last chained method.
     * @var string
     */
    private $currentRouteName = null;

    /**
     * Creates a GET route
     *
     * @param string $path
     * @param array $action
     * @return Route
     */
    public function registerGetRoute(string $path, array $action): Route
    {
        return $this->registerRoute("GET", $path, $action);
    }

    /**
     * Creates a POST route
     *
     * @param string $path
     * @param array $action
     * @return Route
     */
    public function registerPostRoute(string $path, array $action): Route
    {
        return $this->registerRoute("POST", $path, $action);
    }

    /**
     * Creates a PUT route
     *
     * @param string $path
     * @param array $action
     * @return Route
     */
    public function registerPutRoute(string $path, array $action): Route
    {
        return $this->registerRoute("PUT", $path, $action);
    }

    /**
     * Creates a DELETE route
     *
     * @param string $path
     * @param array $action
     * @return Route
     */
    public function registerDeleteRoute(string $path, array $action): Route
    {
        return $this->registerRoute("DELETE", $path, $action);
    }

    /**
     * Adds a new route
     *
     * @param string $method
     * @param string $path
     * @param array $action
     * @return Route
     */
    private function registerRoute(string $method, string $path, array $action): Route
    {
        $action_assoc = $this->bindActionProperties($action);

        $this->currentRouteName = StringTool::generateRandomSerial();

        $this->routes[$method][$this->currentRouteName] = [
            "path" => $path,
            "class" => $action_assoc['class'],
            "function" => $action_assoc['function'],
        ];

        return $this;
    }

    /**
     * Turns the action array list into an associative array
     *
     * @param array $action
     * @return array
     */
    private function bindActionProperties(array $action): array
    {
        return [
            "class" => $action[0],
            "function" => $action[1] ?? null, // null for single action controllers
        ];
    }

    /**
     * Assigns a name to the current route
     *
     * @param string $name  the string to be used to name the route
     * @return Route
     */
    public function name(string $name): Route
    {
        foreach($this->routes as $method => $routeData){
            foreach($routeData as $iteratedRouteName => $iteratedRouteDetails){
                if($iteratedRouteName === $this->currentRouteName){
                    if($this->currentRouteName != $name){
                        // unset the route with the generic name
                        unset($this->routes[$method][$this->currentRouteName]);
                        // drop the genereic route name and set the defined $name value in $this->currentRouteName
                        $this->currentRouteName = $name;
                        // set the route using the given $name
                        $this->routes[$method][$name] = $iteratedRouteDetails;
                    }
                    break 2;
                }
            }
        }

        return $this;
    }

    /**
     * Adds middlewares to the current route
     *
     * @param array $middlewares
     * @return void
     */
    public function middlewares(array $middlewares): Route
    {
        foreach($this->routes as $routeMethod => $routeData){
            foreach($routeData as $routeName => $routeDetails){
                if($routeName === $this->currentRouteName){
                    $this->routes[$routeMethod][$routeName]["middlewares"] = $middlewares;
                    break 2;
                }
            }
        }

        return $this;
    }

    /**
     * Gets all the routes or the routes of an specific method type
     *
     * @param ?string|null $method
     * @return array
     */
    public function all(?string $method = null): array
    {
        if(!is_null($method)){
            return $this->routes[strtoupper($method)];
        }
        return $this->routes;
    }

}