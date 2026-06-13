<?php

namespace Exhaust\Facades\Templating;

use Exhaust\Contracts\TemplateBlueprint;
use League\Plates\Engine;

/**
 * Facade that wraps League\Plates — a native PHP template engine.
 *
 * Templates are plain PHP files; Plates provides layouts, sections, and helper functions.
 * Framework templates are accessible via the 'framework' folder namespace
 * (e.g. 'framework::error/404').
 * Requires league/plates v3+.
 */
class PlatesFacade implements TemplateBlueprint
{
    /**
     * The Plates Engine instance.
     * @var Engine
     */
    private Engine $engine;

    /**
     * Loads the Plates Engine instance from the config file.
     */
    public function __construct()
    {
        $this->engine = require(__DIR__ . '/../../../config/templating/plates.php');
    }

    /**
     * Renders a Plates template into a string.
     *
     * @param string $name    Template name relative to the configured directory,
     *                        or namespaced (e.g. 'framework::error/404')
     * @param array  $context Variables to expose in the template
     * @return string
     */
    public function render(string $name, array $context = []): string
    {
        return $this->engine->render($name, $context);
    }

    /**
     * Plates auto-escapes output via $this->e() inside templates — no global toggle.
     * This method is a no-op; use $this->e($var) in your templates for explicit escaping.
     *
     * @param bool $flag ignored
     */
    public function escapeContent(bool $flag): void {}

    /**
     * Adds a variable available to all templates rendered by this engine.
     *
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->engine->addData([$name => $value]);
    }
}
