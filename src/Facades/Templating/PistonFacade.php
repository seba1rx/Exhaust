<?php

namespace Exhaust\Facades\Templating;

use Exhaust\Contracts\TemplateBlueprint;
use Exhaust\TemplateEngine\Piston;

/**
 * Facade that wraps Piston — the template engine bundled with the Exhaust framework.
 *
 * Piston renders plain PHP templates using output buffering.
 * Template names are relative paths resolved against the configured template directories.
 */
class PistonFacade implements TemplateBlueprint
{
    /**
     * The Piston engine instance.
     * @var Piston
     */
    private Piston $piston;

    /**
     * Ordered list of base directories used to resolve template names.
     * @var array<string>
     */
    private array $templateDirs;

    /**
     * Loads the template directories from config and instantiates the Piston engine.
     */
    public function __construct()
    {
        $this->templateDirs = require(__DIR__ . '/../../../config/templating/piston.php');
        $this->piston       = new Piston();
    }

    /**
     * Renders a Piston template into a string.
     *
     * Resolves $name against each configured template directory in order and
     * delegates to the Piston engine once the file is found.
     *
     * @param string $name    Relative template path (e.g. 'landing/index.php')
     * @param array  $context Variables to expose inside the template
     * @return string         Rendered HTML
     * @throws \Exhaust\Exceptions\LogicException  If the template file cannot be found
     */
    public function render(string $name, array $context = []): string
    {
        foreach($this->templateDirs as $dir){
            $fullPath = rtrim($dir, '/') . '/' . ltrim($name, '/');
            if(file_exists($fullPath)){
                return $this->piston->render($fullPath, $context);
            }
        }

        throw new \Exhaust\Exceptions\LogicException(
            "Piston: template '{$name}' not found in configured directories."
        );
    }

    /**
     * Enables or disables output escaping.
     *
     * Piston does not implement automatic escaping — this is a no-op placeholder.
     * Use PHP's htmlspecialchars() or similar inside your templates as needed.
     *
     * @param bool $flag
     */
    public function escapeContent(bool $flag): void {}

    /**
     * Registers a variable that will be available in every rendered template.
     *
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->piston->addGlobal($name, $value);
    }
}
