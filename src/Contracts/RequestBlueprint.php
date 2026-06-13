<?php

declare(strict_types=1);

namespace Exhaust\Contracts;

/**
 * Contract for HTTP request handling within the framework.
 *
 * Extends UrlBlueprint to cover the full surface of an incoming HTTP request:
 * method detection, payload access, request-type classification (navigation vs.
 * async), remote address, and file uploads.
 *
 * Implementations are expected to hydrate themselves from the current PHP
 * request context ($_SERVER, $_REQUEST, php://input) on construction.
 *
 * For interoperability with PSR-7 (ServerRequestInterface), the concrete
 * implementation provides a toPsr7() adapter method — see Request::toPsr7().
 */
interface RequestBlueprint extends UrlBlueprint
{
    /**
     * Returns the HTTP method of the current request (GET, POST, PUT, DELETE, …).
     *
     * @return string
     */
    public function getRequestMethod(): string;

    /**
     * Returns true when the request was made asynchronously.
     *
     * A request is considered async when the X-Requested-With header is set to
     * either 'XMLHttpRequest' (XHR) or 'fetch' (Fetch API).
     *
     * @return bool
     */
    public function isAsync(): bool;

    /**
     * Returns true when the request is a regular browser navigation.
     *
     * Equivalent to !isAsync().
     *
     * @return bool
     */
    public function isNavigation(): bool;

    /**
     * Returns the remote IP address of the client that issued the request.
     *
     * @return string
     */
    public function getRemoteAddress(): string;

    /**
     * Returns true when the request contains one or more uploaded files.
     *
     * @return bool
     */
    public function hasUpload(): bool;

    /**
     * Returns the full request payload as an associative array.
     *
     * Values are automatically cast to their detected PHP type
     * (int, float, bool, or string).
     *
     * @return array
     */
    public function payloadAsArray(): array;

    /**
     * Returns the value of a single payload key, or false if the key is absent.
     *
     * @param string $var The payload key to look up.
     * @return mixed
     */
    public function getFomPayload(string $var): mixed;

    /**
     * Hydrates the payload from an explicit array or from the current HTTP
     * request body ($_REQUEST + php://input).
     *
     * When $body is null the method reads the live request; pass an array to
     * load a synthetic payload (useful for testing).
     *
     * @param array|null $body Explicit payload data, or null to read from PHP globals.
     * @return void
     */
    public function setPayload(?array $body = null): void;

    /**
     * Replaces the entire payload object in one operation.
     *
     * @param array|\stdClass $payload_data New payload data.
     * @return void
     */
    public function fillPayload(array|\stdClass $payload_data): void;

    /**
     * Sets a single key in the payload, casting the value to its detected type.
     *
     * @param string $var   Payload key.
     * @param mixed  $value Value to store.
     * @return void
     */
    public function setToPayload(string $var, mixed $value): void;
}
