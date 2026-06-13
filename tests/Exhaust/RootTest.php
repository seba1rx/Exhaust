<?php

declare(strict_types=1);

namespace tests\Exhaust;

use PHPUnit\Framework\TestCase;

/**
 * Etapa 1: archivos raíz (sin directorio)
 *
 * Archivos: globalScopeFunctions.php, App.php, Handler.php
 *
 * - App y Handler dependen del bootstrap completo (config, DB, rutas, sesión).
 *   Sus tests de integración requieren ese entorno y se omiten aquí.
 *
 * - globalScopeFunctions.php define clog() — función global probada aquí.
 *
 * - App como singleton puro (sin init()) es testeable de forma mínima.
 */
final class RootTest extends TestCase
{
    // ------------------------------------------------------------------ clog

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('clog')) {
            require_once __DIR__ . '/../../src/globalScopeFunctions.php';
        }
    }

    public function test_clog_function_exists(): void
    {
        $this->assertTrue(function_exists('clog'));
    }

    public function test_clog_with_string_does_not_throw(): void
    {
        clog('test string');
        $this->assertTrue(true);
    }

    public function test_clog_with_array_does_not_throw(): void
    {
        clog(['key' => 'value', 'num' => 1]);
        $this->assertTrue(true);
    }

    public function test_clog_with_object_does_not_throw(): void
    {
        $obj = new \stdClass();
        $obj->prop = 'val';
        clog($obj);
        $this->assertTrue(true);
    }

    public function test_clog_with_int_does_not_throw(): void
    {
        clog(42);
        $this->assertTrue(true);
    }

    // ------------------------------------------------------------------ App structural

    public function test_app_extends_singleton(): void
    {
        $ref = new \ReflectionClass(\Exhaust\App::class);
        $this->assertEquals(\Exhaust\Patterns\Singleton::class, $ref->getParentClass()->getName());
    }

    public function test_app_is_final(): void
    {
        $ref = new \ReflectionClass(\Exhaust\App::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_app_getInstance_returns_app_instance(): void
    {
        $instance = \Exhaust\App::getInstance();
        $this->assertInstanceOf(\Exhaust\App::class, $instance);
    }

    public function test_app_getInstance_is_same_instance(): void
    {
        $a = \Exhaust\App::getInstance();
        $b = \Exhaust\App::getInstance();
        $this->assertSame($a, $b);
    }

    // ------------------------------------------------------------------ App::loadconfiguration

    /**
     * loadconfiguration() calls define() for DEBUG_* constants.
     * define() can only succeed once per constant per process.
     * Using #[RunInSeparateProcess] to isolate constant definition.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_loadconfiguration_sets_conf_object(): void
    {
        $conf = [
            'debug' => ['frontend' => false, 'backend' => false, 'database' => false],
            'constants' => [],
            'template_engine' => [
                'use' => 'twig',
                'configuration' => [
                    'pathToTemplates' => '/templates',
                    'pathToCompilation' => '/cache',
                    'shouldMinifyOutput' => false,
                ],
            ],
        ];

        $app = \Exhaust\App::getInstance();
        $app->loadconfiguration($conf);

        $this->assertIsObject($app->conf);
        $this->assertFalse($app->conf->debug->frontend);
        $this->assertFalse($app->conf->debug->backend);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_loadconfiguration_trims_leading_slash_in_twig_paths(): void
    {
        $conf = [
            'debug' => ['frontend' => false, 'backend' => false, 'database' => false],
            'constants' => [],
            'template_engine' => [
                'use' => 'twig',
                'configuration' => [
                    'pathToTemplates' => '/templates/views',
                    'pathToCompilation' => '/cache/twig',
                    'shouldMinifyOutput' => false,
                ],
            ],
        ];

        $app = \Exhaust\App::getInstance();
        $app->loadconfiguration($conf);

        $this->assertStringStartsNotWith('/', $app->conf->template_engine->configuration->pathToTemplates);
        $this->assertStringStartsNotWith('/', $app->conf->template_engine->configuration->pathToCompilation);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_loadconfiguration_creates_debug_constants(): void
    {
        $conf = [
            'debug' => ['frontend' => true, 'backend' => false, 'database' => false],
            'constants' => [],
            'template_engine' => [
                'use' => 'piston',
                'configuration' => ['shouldMinifyOutput' => false],
            ],
        ];

        $app = \Exhaust\App::getInstance();
        $app->loadconfiguration($conf);

        $this->assertTrue(defined('DEBUG_FRONTEND'));
        $this->assertTrue(DEBUG_FRONTEND);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_loadconfiguration_creates_custom_constants(): void
    {
        $conf = [
            'debug' => ['frontend' => false, 'backend' => false, 'database' => false],
            'constants' => ['MY_CONST' => 'hello'],
            'template_engine' => [
                'use' => 'piston',
                'configuration' => ['shouldMinifyOutput' => false],
            ],
        ];

        $app = \Exhaust\App::getInstance();
        $app->loadconfiguration($conf);

        $this->assertTrue(defined('MY_CONST'));
        $this->assertEquals('hello', MY_CONST);
    }
}
