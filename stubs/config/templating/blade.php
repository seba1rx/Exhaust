<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * illuminate/view Blade template engine configuration.
 *
 * Returns a configured Illuminate\View\Factory instance pointing to both the
 * app and framework template directories, using the shared compilation cache.
 */
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $base_path = realpath(__DIR__ . '/../../');
} else {
    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], 'public');
    $base_path = rtrim($base_path, '/');
}

$tpl_engine_conf = app()->conf->template_engine->configuration;

$viewPaths = [
    "{$base_path}/{$tpl_engine_conf->pathToTemplates}",
    "{$base_path}/{$tpl_engine_conf->pathToFrameworkTemplates}",
];
$cachePath = "{$base_path}/{$tpl_engine_conf->pathToCompilation}";

$filesystem = new Filesystem();
$container  = new Container();
$resolver   = new EngineResolver();
$compiler   = new BladeCompiler($filesystem, $cachePath);

$resolver->register('blade', fn() => new CompilerEngine($compiler, $filesystem));
$resolver->register('php',   fn() => new PhpEngine($filesystem));

$finder     = new FileViewFinder($filesystem, $viewPaths, ['blade.php', 'php']);
$dispatcher = new Dispatcher($container);

$factory = new Factory($resolver, $finder, $dispatcher);
$factory->setContainer($container);

return $factory;
