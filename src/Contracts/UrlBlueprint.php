<?php

namespace Exhaust\Contracts;

/**
 * This interface is a blueprint to implement URL handling:
 * Use whatever URL library or resource to process the URL
 * with the purpose of getting the URL query parameters and values
 */
interface UrlBlueprint
{

    /**
     * Process the URL in order to identify query string parameters
     *
     * @return void
     */
    public function processUri(): void;

    /**
     * identifies the parameters in the query string in the URL and stores them in the property 'params'
     *
     * @param string $query - The query string section of the URL
     * @return void
     */
    public function identifyUriParams(string $query): void;

        /**
     * Returns the value of the parameter present in the query string
     *
     * @param string $paramName - the name of the parameter
     * @return mixed
     */
    public function getUriParam(string $paramName): mixed;

    /**
     * Returns an array of values if the query string contains more than one parameters with the same name but different value
     *
     * @param string $paramName
     * @return void
     */
    public function getAllUriParamWithName(string $paramName): mixed;

    /**
     * Returns true if param exists in the query string, false if not.
     *
     * @param string $paramName
     * @return bool
     */
    public function uriHasParam(string $paramName): bool;

    /**
     * Removes the params from the uri that matches the $paramName
     *
     * @param string $paramName
     * @return void
     */
    public function removeParamFromUri(string $paramName): void;

    /**
     * Sorts the params identified from the URL
     *
     * @return void
     */
    public function sortUriParams(): void;
}