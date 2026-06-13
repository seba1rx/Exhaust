<?php

use Jenssegers\Blade\Blade;

/**
 * Jenssegers Blade template engine configuration.
 *
 * Returns a configured Blade instance pointing to both the app and
 * framework template directories, using the shared compilation cache.
 */
if(empty($_SERVER['DOCUMENT_ROOT'])){
    $base_path = realpath(__DIR__ . '/../../');
}else{
    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], 'public');
    $base_path = rtrim($base_path, '/');
}

$tpl_engine_conf = app()->conf->template_engine->configuration;

$viewPaths = [
    "{$base_path}/{$tpl_engine_conf->pathToTemplates}",
    "{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}",
];
$cachePath = "{$base_path}/{$tpl_engine_conf->pathToCompilation}";

return new Blade($viewPaths, $cachePath);
