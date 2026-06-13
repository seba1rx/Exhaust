<?php

declare(strict_types=1);

namespace tests\Exhaust\TemplateEngine;

use Exhaust\TemplateEngine\Piston;
use PHPUnit\Framework\TestCase;

final class PistonTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = '';
    }

    protected function tearDown(): void
    {
        if ($this->tmpFile && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    private function createTemplate(string $phpCode): string
    {
        $path = tempnam(sys_get_temp_dir(), 'piston_tpl_') . '.php';
        file_put_contents($path, $phpCode);
        $this->tmpFile = $path;
        return $path;
    }

    // --- render with no context ---

    public function test_render_static_template_no_context(): void
    {
        $path = $this->createTemplate('<p>Hello World</p>');
        $piston = new Piston();
        $result = $piston->render($path);
        $this->assertEquals('<p>Hello World</p>', $result);
    }

    public function test_render_template_that_uses_php(): void
    {
        $path = $this->createTemplate('<?php echo strtoupper("hello"); ?>');
        $piston = new Piston();
        $result = $piston->render($path);
        $this->assertEquals('HELLO', $result);
    }

    // --- addGlobal + render ---

    public function test_addGlobal_makes_variable_available_in_template(): void
    {
        // NOTE: Due to a known bug in Piston::render() — the foreach loop variable
        // $name shadows the $file path parameter — context keys must be added via
        // addGlobal() to safely render templates with dynamic data.
        $path = $this->createTemplate('<?php echo $greeting; ?>');
        $piston = new Piston();
        $piston->addGlobal('greeting', 'Hi from global');
        $result = $piston->render($path);
        $this->assertEquals('Hi from global', $result);
    }

    public function test_addGlobal_multiple_globals(): void
    {
        $path = $this->createTemplate('<?php echo "$firstName $lastName"; ?>');
        $piston = new Piston();
        $piston->addGlobal('firstName', 'John');
        $piston->addGlobal('lastName', 'Doe');
        $result = $piston->render($path);
        $this->assertEquals('John Doe', $result);
    }

    // --- escapeContent (no-op) ---

    public function test_escapeContent_does_not_throw(): void
    {
        $piston = new Piston();
        $piston->escapeContent(true);
        $piston->escapeContent(false);
        $this->assertTrue(true); // reached without exception
    }

    // --- render with missing template ---

    public function test_render_with_missing_template_returns_empty_string(): void
    {
        // include() of a non-existent file generates E_WARNING, not a PHP exception,
        // so Piston's catch(\Exception) is never triggered — it returns ''.
        $piston = new Piston();
        $result = @$piston->render('/non/existent/template_' . uniqid() . '.php');
        $this->assertSame('', $result);
    }

    // --- known bug documentation ---

    public function test_render_passes_context_vars_to_template(): void
    {
        $path = $this->createTemplate('<?php echo $foo; ?>');
        $piston = new Piston();
        $result = $piston->render($path, ['foo' => 'bar']);
        $this->assertSame('bar', $result);
    }
}
