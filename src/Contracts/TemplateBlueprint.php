<?php

namespace Exhaust\Contracts;


/**
 * This interface is a blueprint to implement a template engine:
 * Use whatever template engine you want by creating a wrapper and
 * implementing it to that wrapper if not already provided in this
 * framework in order to work with that template engine
 * * Check directory Core/
 */
interface TemplateBlueprint
{

    /**
     * The main method the template engine of choice has to render the templates
     * @param string $name
     * @param array $context
     * @return string
     */
    public function render(string $name, array $context = []): string;

    /**
     * The main method the template engine of choice has to escape the content to be rendered
     * @param bool $flag
     */
    public function escapeContent(bool $flag);

    /**
     * Sets the key => values as globaly available vars in the templates
     * @param string $name
     * @param mixed $value
     */
    public function addGlobal(string $name, mixed $value);

}