<?php

use League\Plates\Engine;

/**
 * League\Plates template engine configuration.
 *
 * Returns a configured Engine instance.
 * App templates are loaded from the primary directory; framework templates
 * are registered under the 'framework' folder namespace (e.g. 'framework::error/404').
 */
if(empty($_SERVER['DOCUMENT_ROOT'])){
    $base_path = realpath(__DIR__ . '/../../');
}else{
    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], 'public');
    $base_path = rtrim($base_path, '/');
}

$tpl_engine_conf = app()->conf->template_engine->configuration;

$engine = new Engine("{$base_path}/{$tpl_engine_conf->pathToTemplates}");
$engine->addFolder('framework', "{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}");

return $engine;
