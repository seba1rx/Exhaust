<?php

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
$templatesBaseDir = [
    "{$base_path}/{$tpl_engine_conf->pathToTemplates}",
    "{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}",
];
$compilationDir = "{$base_path}/{$tpl_engine_conf->pathToCompilation}";

$loader = new \Twig\Loader\FilesystemLoader($templatesBaseDir);
$twig = new \Twig\Environment($loader, [
    'cache' => $compilationDir,
    // 'cache' => false, // disable the cache
    'debug' => $tpl_engine_conf->options->debug,
    'strict_variables' => $tpl_engine_conf->options->strict_variables,
]);
$twig->addGlobal(name: 'DEBUG_FRONTEND', value: DEBUG_FRONTEND);

return $twig;