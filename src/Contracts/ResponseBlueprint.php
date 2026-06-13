<?php

declare(strict_types=1);

namespace Exhaust\Contracts;

/**
 * Contract for HTTP responses produced by the framework.
 *
 * A framework response is a validated set of Commands that the frontend
 * engine (engeen.js) interprets and executes. Two serialisation formats
 * are supported depending on the originating request type:
 *
 *  - Navigation requests  → Content-Type: text/html  (the 'echo' command body)
 *  - Async requests       → Content-Type: application/json  (all commands as JSON)
 *
 * For pure REST API consumers, Commands::apiResponse() places a plain JSON
 * object under the 'api' key; this is still delivered as JSON but is not
 * intended to be processed by engeen.js.
 *
 * Implementations hold the final, validated command set and are responsible
 * for selecting the correct format and writing the response to the output stream.
 */
interface ResponseBlueprint
{
    /**
     * Returns the full set of validated commands that will be sent to the client.
     *
     * The array structure mirrors the JSON object received by engeen.js,
     * e.g. ['html' => ['node-id' => '<p>…</p>'], 'toast' => […]].
     *
     * @return array
     */
    public function getCommands(): array;

    /**
     * Serialises the command set as a JSON string.
     *
     * Used for async (XHR / Fetch) responses. The resulting string is the
     * body that engeen.js — or any JSON API consumer — receives.
     *
     * @return string JSON-encoded command object.
     */
    public function toJson(): string;

    /**
     * Returns the raw HTML string for navigation (full-page) responses.
     *
     * Reads the value stored under the 'echo' command key, which must have
     * been set via Commands::echo() before this method is called.
     *
     * @return string The HTML body to deliver to the browser.
     * @throws \Exhaust\Exceptions\LogicException When no 'echo' command is present.
     */
    public function toHtml(): string;

    /**
     * Writes the response to the HTTP output stream.
     *
     * Sets the appropriate Content-Type header and echoes the serialised body:
     * - toHtml() for navigation requests.
     * - toJson() for async (XHR / Fetch) requests.
     *
     * @return void
     */
    public function send(): void;
}
