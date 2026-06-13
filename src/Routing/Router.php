<?php

namespace Exhaust\Routing;

use App\Controllers\NotFound;
use Exhaust\Tools\StringTool;

final class Router
{

    /**
     * Holds the defined routes
     * @var array
     */
    protected $routes;

    /**
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes['GET'] = $routes['GET'];
        $this->routes['POST'] = $routes['POST'];
        $this->routes['PUT'] = $routes['PUT'];
        $this->routes['DELETE'] = $routes['DELETE'];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Identifies the route to be used by analyzing the uri, instantiates the controller and executes the action
     *
     * @return array
     * @throws \Exception
     */
    public function direct(): array
    {
        $payload = app()->request->payloadAsArray();
        $method = app()->request->getRequestMethod();
        $uri_route = app()->request->uri->path;

        $identifiedRoute = $this->identifyRoute($uri_route, $method);

        if($identifiedRoute === false){
            $obj = new NotFound();
            return $obj();
        }

        if(isset($identifiedRoute['function'])){
            ## normal controller, calls a method to handle the request

            $className = $identifiedRoute['class'];
            $functionName = $identifiedRoute['function'];

            $middlewares = [];
            if(isset($identifiedRoute['middlewares'])){
                ## all these middlewares are "before controller"
                $middlewares = $identifiedRoute['middlewares'];
            }

            ## create the controller object using the class name
            $obj = new $className($middlewares);

            ## check if method is callable, else throw exception
            if(is_callable([$obj, $functionName])){

                ## if after object creation the app determines no to continue to the method, then terminate
                if(app()->terminate_session){
                    return [];
                }

                ## calls the controller->method(payload)
                return $obj->$functionName($payload);
            }else{

                throw new \Exception("There is no action associated to the route");
            }

        }else{
            ## single action controllers, invokes action

            $className = $identifiedRoute['class'];

            ## invoke classObject()
            $obj = new $className();
            return $obj($payload);
        }
    }

    /**
     * Identifies the route to use from the uri
     *
     * + make sure to check the logging section in the config.php file to see the log output
     *
     * @param string $uri_route
     * @param string $request_method
     * @return array|false
     */
    private function identifyRoute(string $uri_route, string $request_method): array|false
    {
        ## remove possible "/" at beginning of string to prevent empty first segment when exploding
        if(str_starts_with(trim($uri_route), "/") && trim($uri_route) != "/"){
            $uri_route = ltrim($uri_route, "/");
        }

        $uri_route_segments = $this->getRouteSegments(uri_route: $uri_route);
        if(empty($uri_route_segments)){
            ## if segments are an empty array then return base route
            return $this->routes['GET']['base'];
        }else{
            $position = 0;

            foreach($uri_route_segments as $uri_route_segment){
                if($position == 0){
                    ## try to identify the route by the first segment of the given uri route by comparing against the loaded routes

                    ## here we iterate the routes loaded to the system
                    foreach($this->routes[$request_method] as $route_name => $route_info){

                        ## in order to check against the system routes, lets remove the staring "/" of each route
                        $possible_route = StringTool::getStringAfterFirst(needle: "/", haystack: $route_info['path']);
                        $segments_in_iterated_route = $this->getRouteSegments(uri_route: $possible_route);

                        if(count($uri_route_segments) != count($segments_in_iterated_route)){
                            ## not the same amounts of segments, so the requested route is not the iterated route
                            continue;
                        }

                        $first_segment_in_iterated_route = $segments_in_iterated_route[0];

                        ## it first segment matches then check the other segments
                        if($first_segment_in_iterated_route == $uri_route_segment){

                            ## add the iterated route args (if any) to the $route_info var
                            $args = $this->identifyPathArgs(exploted_url_route: $uri_route_segments, exploted_loaded_route: $segments_in_iterated_route);

                            ## check every segment of the uri route against the iterated configured system route
                            foreach($segments_in_iterated_route as $index => $iterated_system_route_segment){
                                foreach($args as $arg){
                                    if($arg['position'] == $index){
                                        ## only checking segments that are not a parameter, ignoring
                                    }else{
                                        if($iterated_system_route_segment !== $segments_in_iterated_route[$index]){
                                            ## go to next system route
                                            continue 3;
                                        }
                                    }
                                }
                            }

                            ## ifexecution reaches this point then the iterated route matches the uri route
                            return $this->routes[$request_method][$route_name];
                        }
                    }
                }else{
                    ## no need to keep iterating after first loop
                    break;
                }

                $position++;
            }
        }

        return false;
    }

    /**
     * Returns the segments of the requested route (uri route)
     *
     * @param string $uri_route
     * @return array
     */
    private function getRouteSegments(string $uri_route): array
    {
        if($uri_route == "" || $uri_route == "/"){
            return [];
        }

        return explode("/", $uri_route);
    }

    /**
     * Identifies the route parameters
     *
     * @param array $exploted_url_route - the route segments in the url
     * @param array $exploted_loaded_route - the route segments of the possible configured route to check against
     * @return array|false
     */
    private function identifyPathArgs(array $exploted_url_route, array $exploted_loaded_route): array
    {
        $params = [];

        foreach($exploted_loaded_route as $index => $segment){
            if(StringTool::isWrappedBetween(haystack: $segment, firstChar: '{', finalChar: '}')){
                $varWithNoBraces = str_replace(['{', '}'], "", $segment);
                $var_value_in_uri_segment = $exploted_url_route[$index];
                $params[] = [
                    "name" => "$varWithNoBraces",
                    "type" => $this->identifyParamType($var_value_in_uri_segment),
                    "position" => $index,
                    "value" => $var_value_in_uri_segment,
                ];
            }
        }

        return $params;
    }

    /**
     * Identifies the data type of the uri param using the uri segment value and the (possible) configured route segment
     *
     * @param string $var_value_in_uri_segment
     * @return string
     */
    private function identifyParamType(string $var_value_in_uri_segment): string
    {
        if(empty($var_value_in_uri_segment)){
            throw new \Exception("An error occured when trying to identify a parameter in the route to the resource");
        }

        $identifiedType = "string";
        if(strtoupper($var_value_in_uri_segment) === "TRUE" || strtoupper($var_value_in_uri_segment) === "FALSE"){
            $identifiedType = "boolean";
        }
        if(is_numeric($var_value_in_uri_segment)){
            if(is_double($var_value_in_uri_segment)){
                $identifiedType = "double";
            }else{
                $identifiedType = "integer";
            }
        }

        return $identifiedType;
    }
}