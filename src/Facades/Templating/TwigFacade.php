<?php

namespace Exhaust\Facades\Templating;

use Exhaust\Contracts\TemplateBlueprint;
use \Twig\Environment;

class TwigFacade implements TemplateBlueprint
{
    /**
     * The Twig object
     * @var \Twig\Environment
     */
    public $twig;

    /**
     * The flag to indicate whether the template
     * engine should escape the contents or not
     * @var bool
     */
    private $escapeFlag = false;

    public function __construct()
    {
        $this->twig = require(__DIR__ . '/../../../config/templating/twig.php');
    }

    /**
     * Renders a Twig template into a string
     *
     * Wrapper method arround:
     * + $twig->render("template.twig", ["foo" => "bar"]);
     *
     * @param string $name the name of the template file
     * @param string $name the vars in an assoc array to be made accessible in the template context
     * @return string
     */
    public function render(string $name, array $context = []): string
    {
        return $this->twig->render(
            name: $name,
            context: $context
        );
    }

    /**
     * The main method the template engine of choice has to escape the content to be rendered
     * + should be called before the render (fetch) method
     *
     * @param bool $flag
     */
    public function escapeContent(bool $flag)
    {
        $this->twig->escapeFlag = $flag;
    }

    /**
     * Sets the key => values as globaly available vars in the templates
     * @param string $name
     * @param mixed $value
     */
    public function addGlobal(string $name, mixed $value)
    {
        $this->twig->addGlobal($name, $value);
    }
}