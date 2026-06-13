<?php

namespace Exhaust\Facades\Templating;

use Exhaust\Contracts\TemplateBlueprint;
use \Smarty\Smarty;

class SmartyFacade implements TemplateBlueprint
{

    /**
     * The Smarty instance
     * @var \Smarty\Smarty
     */
    public $smarty;

    /**
     * The flag to indicate whether the template
     * engine should escape the contents or not
     * @var bool
     */
    private $escapeFlag = false;

    public function __construct()
    {
        // $this->smarty = new Smarty;
        $this->smarty = require(__DIR__ . '/../../../config/templating/smarty.php');
    }

    /**
     * Renders a Smarty template into a string.
     *
     * Assigns each context variable before calling fetch(), which returns the rendered HTML.
     *
     * @param string $name    Template file name, resolved from the configured template directories
     * @param array  $context Associative array of variables to expose in the template
     * @return string
     */
    public function render(string $name, array $context = []): string
    {
        foreach($context as $var_name => $value){
            $this->smarty->assign($var_name, $value);
        }

        return $this->smarty->fetch($name);
    }

    /**
     * Enables or disables HTML escaping for variables output in templates.
     *
     * Maps to Smarty's escape_html modifier setting.
     *
     * @param bool $flag true to enable escaping, false to disable
     */
    public function escapeContent(bool $flag): void
    {
        $this->smarty->escape_html = $flag;
    }

    /**
     * Sets the key => value as a globaly available var
     *
     * @param string $name
     * @param mixed $value
     */
    public function addGlobal(string $name, mixed $value)
    {
        // The third parameter 'true' makes it global
        $this->smarty->assign($name, $value, true);
    }
}
