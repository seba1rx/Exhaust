<?php

namespace Exhaust\TemplateEngine;

use Exhaust\Exceptions\LogicException;
use Exhaust\Tools\CastingTool;
use Exhaust\Contracts\TemplateBlueprint;


/**
 * "Piston" is the name for the template engine packed in Exhaust Framework
 *
 * Although this class will work as intended it is an exercise to
 * create a template engine, and it is not suitable for production
 * since it has not been fully tested
 */
class Piston implements TemplateBlueprint
{

    /**
     * Array of global vars that are accessible from any template
     * @var array
     */
    private array $globals = [];

    /**
     * Renders the template
     *
     * @param string $name - the template file to render
     * @param mixed $context - the vars to pass to the template
     * @return string
     * @throws LogicException
     */
    public function render(string $name, mixed $context = []): string
    {
        try{
            // if not an assoc array, cast to assoc array
            $castedVars = CastingTool::preferAssocArray($context);

            // starts the buffer
            ob_start();

            // add the global vars to the buffer scope
            foreach($this->globals as $global_name => $global_value){
                ${$global_name} = $global_value;
            }

            // add the vars to the buffer scope
            foreach ($castedVars as $key => $value) {
                ${$key} = $value;
            }

            // add the template to the buffer in order to render the content
            include $name;

            // get the rendered contents as a string from the buffer, end buffer
            $html_content = ob_get_clean();

            return $html_content;

        }catch(\Exception $e){

            throw new LogicException("An error occurred while processing the template {$name}: {$e->getMessage()}");
        }
    }

    /**
     * The main method the template engine of choice has to escape the content to be rendered
     */
    public function escapeContent(bool $flag): void
    {}

    /**
     * Adds a variable to the scope of any template
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->globals[$name] = $value;
    }

}