<?php

require_once __DIR__.'/../vendor/autoload.php';

/**
 * The global scoped Throwable handler
 */
include __DIR__ . "/Exceptions/handler.php";

/**
 * include file that has the definition of constants into global scope
 */
include __DIR__ . "/../config/constants.php";

/**
 * The config file data
 */
$conf = require __DIR__ . '/../config/config.php';

/**
 * Global funtion to access the singleton app instance.
 * The app instance acts like a config manager and holds
 * other instances for a globally accessible centralized
 * configuration and resource manager
 *
 * @return Exhaust\App
 */
function app(): Exhaust\App
{
    return Exhaust\App::getInstance();
}

/**
 * Gets the configuration object
 *
 * @return object
 */
function conf(): object
{
    return app()->conf;
}

$app = app();
$app->loadConfiguration($conf);

/**
 * Find out if runtime is in cli or an http request.
 * Must be checked before init() since Request parses $_SERVER HTTP headers.
 */
$isCLI = (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');

include __DIR__ . "/../config/eloquent.php";

if($isCLI){
    /**
     * if the client is making a request from CLI then
     * skip the session and template management
     */
    echo Exhaust\Logging\CliLog::info("Bootstrapping the app from CLI, ignoring template engine");
    echo Exhaust\Logging\CliLog::info("Bootstrapping the app from CLI, ignoring PHP Session management");
}else{
    $app->init();
    /** if client is making an XHR/HTTP request */

    /**
     * ################################
     * Start/restart the PHP session
     */
    $engineSessionAdmin = new Exhaust\Session\ExhaustSessionAdmin(["sessionLifetime" => $app->conf->session->sessionLifetime]);
    $engineSessionAdmin->activateSession();
    $app->sessionManager = $engineSessionAdmin;

    // /**
    //  * ################################
    //  * Template engine configuration
    //  *
    //  * TODO: move to another file, make it more readable
    //  */
    // if(empty($_SERVER['DOCUMENT_ROOT'])){
    //     ## cli
    //     $base_path = __DIR__ . "/../";
    // }else{
    //     ## web server
    //     $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], "public");
    //     $base_path = rtrim($base_path, "/");
    // }
    // $tpl_engine_conf = $app->conf->template_engine->configuration;
    // $templatesBaseDir = "{$base_path}/{$tpl_engine_conf->pathToTemplates}";
    // $compilationCacheDir = "{$base_path}/{$tpl_engine_conf->pathToCompilationCache}";

    // if($app->conf->template_engine->use == "twig"){
    //     /**
    //      * Twig configuration
    //      *
    //      * In order to get a clean path to the template files lets get the base path of the app.
    //      * In docker it should be in "/var/www/html/" but in local dev it is in /home.
    //      * It is better to calculate the base path so it is compatible with any file system
    //      */
    //     $loader = new \Twig\Loader\FilesystemLoader($templatesBaseDir);
    //     $twig = new \Twig\Environment($loader, [
    //         'cache' => $compilationCacheDir,
    //         // 'cache' => false, // disable the cache
    //         'debug' => $tpl_engine_conf->options->debug,
    //         'strict_variables' => $tpl_engine_conf->options->strict_variables,
    //     ]);
    //     $twig->addGlobal(name: 'DEBUG_FRONTEND', value: DEBUG_FRONTEND);
    //     $app->templateExhaust = $twig;

    // }elseif($app->conf->template_engine->use == "piston"){
    //     /**
    //      * Use default template engine.
    //      *
    //      * The Exhaust framework comes with a template engine called "Piston"
    //      */
    //     $piston = new Exhaust\TemplateExhaust\Piston();
    //     $piston->addGlobal(name: 'DEBUG_FRONTEND', value: DEBUG_FRONTEND);
    //     $app->templateExhaust = $piston;

    //     // TODO
    // }elseif($app->conf->template_engine->use == "smarty"){

    //     // TODO real config

    // }else{
    //     // TODO throw exception
    // }

}

