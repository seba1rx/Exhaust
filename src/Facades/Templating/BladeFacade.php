<?php

namespace Exhaust\Facades\Templating;

use Exhaust\Contracts\TemplateBlueprint;
use Illuminate\View\Factory;

/**
 * Facade that wraps illuminate/view's Blade engine.
 *
 * Template names follow Blade's dot-notation convention (e.g. 'landing.index').
 * Blade auto-escapes output with {{ }}, so escapeContent() is a no-op.
 */
class BladeFacade implements TemplateBlueprint
{
    /**
     * The illuminate/view Factory instance.
     * @var Factory
     */
    private Factory $blade;

    /**
     * Loads the Factory instance from the config file.
     */
    public function __construct()
    {
        $this->blade = require(__DIR__ . '/../../../config/templating/blade.php');
    }

    /**
     * Renders a Blade template into a string.
     *
     * @param string $name    Template name in dot notation (e.g. 'folder.view')
     * @param array  $context Variables to expose in the template
     * @return string
     */
    public function render(string $name, array $context = []): string
    {
        return $this->blade->make($name, $context)->render();
    }

    /**
     * Blade auto-escapes output via {{ }} — no explicit toggle needed.
     * Use {!! !!} inside templates when raw output is intentional.
     *
     * @param bool $flag ignored
     */
    public function escapeContent(bool $flag): void {}

    /**
     * Shares a variable across all Blade views.
     *
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->blade->share($name, $value);
    }
}
