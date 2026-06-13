<?php

use \Smarty\Smarty;

/**
 * In order to get a clean path to the template files lets get the base path of the app.
 * In docker it should be in "/var/www/html/" but in local dev it is in /home.
 * It is better to calculate the base path so it is compatible with any file system
 */
if(empty($_SERVER['DOCUMENT_ROOT'])){
    ## cli
    $base_path = __DIR__ . "/../";
}else{
    ## web server
    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], "public");
    $base_path = rtrim($base_path, "/");
}
$tpl_engine_conf = app()->conf->template_engine->configuration;

$smarty = new Smarty();
$smarty->cache_lifetime = 300;

$smarty->setTemplateDir([
    "{$base_path}/{$tpl_engine_conf->pathToTemplates}",
    "{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}",
]);

$smarty->setCompileDir("{$base_path}/{$tpl_engine_conf->pathToCompilation}");
$smarty->setCacheDir("{$base_path}/{$tpl_engine_conf->pathToCache}");
return $smarty;



