<?php

declare(strict_types=1);

namespace Exhaust\Request;

use Exhaust\Contracts\RequestBlueprint;
use League\Uri\Uri;
use League\Uri\Components\URLSearchParams;
use Exhaust\Tools\StringTool;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Represents the current HTTP request.
 *
 * Hydrates itself from PHP superglobals ($_SERVER, $_REQUEST, php://input) on
 * construction. Provides typed access to the payload, URI components, headers,
 * cookies, and uploaded files.
 *
 * Implements RequestBlueprint (which extends UrlBlueprint) and exposes a
 * toPsr7() adapter for interoperability with PSR-7 / PSR-15 middleware.
 */
class Request implements RequestBlueprint
{

    /**
     * Holds the query string parameters in an associative array
     * @var URLSearchParams
     */
    private $params;

    /**
     * Holds the request data received
     * @var \stdClass
     */
    public $payload;

    /**
     * Holds the detected IP address from the client that is requesting a resource
     * @var string
     */
    private $remoteAddress;

    /**
     * Holds the uri data.
     * @var \stdClass
     */
    public $uri;

    /**
     * The method used for the request
     * @var string
     */
    protected $method;

    /**
     * Holds the cookies
     * @var \stdClass
     */
    protected $cookies;

    /**
     * The type of request: Navigation, XHR or Fetch
     * @var RequestType
     */
    public RequestType $requestType;

    /**
     * According to config->request->ignore the
     * list of directories or path to be ignored
     * @var bool
     */
    public $shouldBeIgnored = false;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->payload = new \stdclass;
        $this->uri = new \stdclass;
        $this->processUri();
        $this->setShouldBeIgnored();
        $this->setPayload();
        $this->setRemoteAddress();
        $this->setRequestMethod();
        $this->setRequestType();
    }


    private function setRequestMethod(): void
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Returns the request method
     *
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->method;
    }

    /**
     * Implementation of the abstract class UrlInterface.
     * Process the URL query string
     *
     * @return void
     */
    public function processUri(): void
    {
        $requestUri = Uri::fromServer($_SERVER);
        $query = $requestUri->getQuery();

        $this->uri->scheme = $requestUri->getScheme();
        $this->uri->host = $requestUri->getHost();
        $this->uri->path = $requestUri->getPath();
        $this->uri->string = $requestUri->toString();
        $this->uri->query = $query;

        if(!is_null($query)){
            $this->identifyUriParams($query);
        }
    }

    /**
     * Implementation of the abstract class UrlInterface.
     * Wrapper function of League\Uri\Components\URLSearchParams::get().
     * Returns the value of the identified query string parameter
     *
     * @param string $paramName - the name of the parameter
     * @return mixed
     */
    public function getUriParam(string $paramName): mixed
    {
        return $this->params->get($paramName);
    }

    /**
     * Implementation of the abstract class UrlInterface.
     * Identifies the query string parameters and stores them in the local var $params
     * @param string $query
     * @return void
     */
    public function identifyUriParams(string $query): void
    {
        $this->params = new URLSearchParams($query);
    }

    /**
     * Wrapper function of League\Uri\Components\URLSearchParams::getAll().
     * Returns an array of values if the query string contains one or more parameters with the same name but different value
     *
     * @param string $paramName
     * @return mixed
     */
    public function getAllUriParamWithName(string $paramName): mixed
    {
        return $this->params->getAll($paramName);
    }

    /**
     * Wrapper function of League\Uri\Components\URLSearchParams::has().
     * Returns true if param exists in the query string, false if not.
     *
     * @param string $paramName
     */
    public function uriHasParam(string $paramName): bool
    {
        return $this->params->has($paramName);
    }

    /**
     * Wrapper function of League\Uri\Components\URLSearchParams::delete().
     * Removes the params from the uri that matches the $paramName
     *
     * @param string $paramName
     * @return void
     */
    public function removeParamFromUri(string $paramName): void
    {
        $this->params->delete($paramName);
    }

    /**
     * Wrapper function of League\Uri\Components\URLSearchParams::sort().
     * Sorts the params identified from the URL
     *
     * @return void
     */
    public function sortUriParams(): void
    {
        $this->params->sort();
    }

    /**
     * Loads all body data parameters from
     * an array given or from the request body
     *
     * @param array $body
     * @return void
     */
    public function setPayload(?array $body = null): void
    {
        if (is_array($body)) {
            $requestBody = $body;
        } else {
            $requestBody = $_REQUEST;
        }

        foreach ($requestBody as $var => $value) {
            $this->payload->{$var} = $this->castToDetectedType($value);
        }

        ## vars from raw body
        $raw_body_data = json_decode(file_get_contents('php://input'), true);
        if ($raw_body_data != null) {
            foreach ($raw_body_data as $key => $value) {
                $this->payload->$key = $this->castToDetectedType($value);
            }
        }
    }

    /**
     * Casts the value to the identified type (int, double, bool, string)
     *
     * @param mixed $value
     */
    private function castToDetectedType(mixed $value): mixed
    {
        $str_bools = ["TRUE", "FALSE"];

        if (is_numeric($value)) {
            if(is_int($value)){
                return (int)$value;
            }
            return (double)$value;
        }else{
            $value = (string)$value;
            if(in_array(strtoupper($value), $str_bools)){
                return (bool)$value;
            }else{
                return $value;
            }
        }
    }

    /**
     * Returns the value of a var from the payload property.
     *
     * @param string $var
     * @return mixed
     */
    public function getFomPayload(string $var): mixed
    {
        if (property_exists($this->payload, $var)) {
            return $this->payload->{$var};
        }
        return false;
    }

    /**
     * Fills payload property with array
     *
     * @param array|\stdClass $payload_data
     */
    public function fillPayload(array|\stdClass $payload_data): void
    {
        $this->payload = $payload_data;
    }

    /**
     * Set a value in the payload property.
     *
     * @param string $var
     * @param mixed $value
     * @return void
     */
    public function setToPayload(string $var, mixed $value): void
    {
        $this->payload->{$var} = $this->castToDetectedType($value);
    }

    /**
     * Returns the payload as an associative array
     *
     * @return array
     */
    public function payloadAsArray(): array
    {
        return $this->toArray($this->payload);
    }

    /**
     * Determines if request has uploaded files
     *
     * @return bool
     */
    public function hasUpload(): bool
    {
        if (!$_FILES) {
            return false;
        }

        foreach ($_FILES as $var => $value) {
            if (is_array($value)) {
                if ($value['tmp_name'][0]) {
                    return true;
                }
            } else {
                if ($value['tmp_name']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Takes an array or stdClass object and casts it to array
     *
     * @param array|\stdClass $var
     * @return array
     */
    public function toArray(array|\stdClass $var): array
    {
        return json_decode(json_encode($var), true);
    }

    /**
     * Takes an array or stdClass object and casts it to object
     *
     * @param array|\stdClass $var
     * @return object
     */
    public function toObject(array|\stdClass $var): object
    {
        return json_decode(json_encode($var));
    }

    /**
     * Gets the data in the payload and returns it as an array, keeps the payload intact
     * @return array
     */
    public function payloadToArray()
    {
        return $this->toArray($this->payload);
    }

    /**
     * Sets the remote address from the client in a property of this class.
     *
     * @see https://stackoverflow.com/questions/44085102/php-most-accurate-safe-way-to-get-real-user-ip-address-in-2017
     * @return void
     */
    private function setRemoteAddress(): void
    {
        $this->remoteAddress = (string)$_SERVER['REMOTE_ADDR'];
    }

    /**
     * Gets the value of the remote address property
     *
     * @return string
     */
    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    /**
     * Sets the request type based on the X-Requested-With header.
     * Fetch requests must send the header manually: X-Requested-With: fetch
     * @return void
     */
    private function setRequestType(): void
    {
        $xRequestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        $this->requestType = match(true) {
            $xRequestedWith === 'xmlhttprequest' => RequestType::XHR,
            $xRequestedWith === 'fetch'          => RequestType::Fetch,
            default                              => RequestType::Navigation,
        };
    }

    /**
     * Returns true if the request was made asynchronously (XHR or Fetch)
     */
    public function isAsync(): bool
    {
        return $this->requestType !== RequestType::Navigation;
    }

    /**
     * Returns true if the request is a regular browser navigation
     */
    public function isNavigation(): bool
    {
        return $this->requestType === RequestType::Navigation;
    }

    /**
     * Converts this request into a PSR-7 ServerRequestInterface object.
     *
     * The conversion is on-demand and read-only: the returned object is an
     * immutable PSR-7 view of the current request state, suitable for passing
     * to PSR-15 middleware that expects a ServerRequestInterface.
     *
     * The caller must supply a PSR-17 ServerRequestFactoryInterface. Any
     * PSR-17-compliant library works (e.g. nyholm/psr7, guzzlehttp/psr7).
     *
     * Example:
     *   $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
     *   $psr7 = app()->request->toPsr7($factory);
     *   $response = $someMiddleware->process($psr7, $handler);
     *
     * @param ServerRequestFactoryInterface $factory PSR-17 factory used to build the object.
     * @return ServerRequestInterface
     */
    public function toPsr7(ServerRequestFactoryInterface $factory): ServerRequestInterface
    {
        $psr7 = $factory->createServerRequest(
            method: $this->method,
            uri: $this->uri->string,
            serverParams: $_SERVER,
        );

        foreach ($_SERVER as $key => $value) {
            if (\str_starts_with($key, 'HTTP_')) {
                $name = \str_replace('_', '-', \strtolower(\substr($key, 5)));
                $psr7 = $psr7->withHeader($name, $value);
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $psr7 = $psr7->withHeader('Content-Type', $_SERVER['CONTENT_TYPE']);
        }

        if (!empty($this->uri->query)) {
            \parse_str($this->uri->query, $queryParams);
            $psr7 = $psr7->withQueryParams($queryParams);
        }

        if (!empty($_COOKIE)) {
            $psr7 = $psr7->withCookieParams($_COOKIE);
        }

        $psr7 = $psr7->withParsedBody($this->payloadAsArray());

        return $psr7;
    }

    /**
     * Evaluates if the current request starts with a path that is marked as
     * ignorable in conf->request->ignore
     *
     * @return void
     */
    private function setShouldBeIgnored(): void
    {
        $path = $this->uri->path;
        if(str_starts_with($path, "/")) $path = ltrim($path, "/");
        if(!empty($path)){
            $firstSegment = StringTool::getStringBeforeFirst("/", $path);
            foreach(app()->conf->request->ignore as $ignorablePath){
                if($firstSegment == $ignorablePath) $this->shouldBeIgnored = true;
                break;
            }
        }
    }
}
