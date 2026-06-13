<?php

/**
 * Piston template engine configuration.
 *
 * Returns the ordered list of directories Piston will search when resolving
 * a template name. App templates are checked first; framework templates second.
 */
if(empty($_SERVER['DOCUMENT_ROOT'])){
    $base_path = realpath(__DIR__ . '/../../');
}else{
    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], 'public');
    $base_path = rtrim($base_path, '/');
}

$tpl_engine_conf = app()->conf->template_engine->configuration;

return [
    realpath("{$base_path}/{$tpl_engine_conf->pathToTemplates}"),
    realpath("{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}"),
];
