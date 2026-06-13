<?php

declare(strict_types=1);

namespace tests\Exhaust\Exceptions;

use Exhaust\Exceptions\LogicException;
use PHPUnit\Framework\TestCase;

final class LogicExceptionTest extends TestCase
{
    public function test_is_throwable(): void
    {
        $e = new LogicException('test message');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(\Throwable::class, $e);
    }

    public function test_message_is_accessible(): void
    {
        $e = new LogicException('Something went wrong');
        $this->assertEquals('Something went wrong', $e->getMessage());
    }

    public function test_can_be_caught_as_exception(): void
    {
        $caught = false;
        try {
            throw new LogicException('caught it');
        } catch (\Exception $e) {
            $caught = true;
            $this->assertEquals('caught it', $e->getMessage());
        }
        $this->assertTrue($caught);
    }

    public function test_invoke_sets_500_response_and_returns_json(): void
    {
        // Build a minimal config object
        $conf = json_decode(json_encode([
            'debug'     => ['backend' => false],
            'exception' => ['show_trace' => false, 'show_detail' => false],
        ]));

        $e = new LogicException('invoke test');

        ob_start();
        ($e)($e, $conf);
        $output = ob_get_clean();

        $this->assertEquals(500, http_response_code());

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('log', $decoded);
        $this->assertArrayHasKey('dialog', $decoded);
    }

    public function test_invoke_includes_debug_info_when_backend_debug_on(): void
    {
        $conf = json_decode(json_encode([
            'debug'     => ['backend' => true],
            'exception' => ['show_trace' => false, 'show_detail' => false],
        ]));

        $e = new LogicException('debug message');

        ob_start();
        ($e)($e, $conf);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('debug', $decoded['log']);
        $this->assertEquals('debug message', $decoded['log']['debug']['msg']);
    }

    public function test_invoke_shows_detail_in_dialog_when_enabled(): void
    {
        $conf = json_decode(json_encode([
            'debug'     => ['backend' => false],
            'exception' => ['show_trace' => false, 'show_detail' => true],
        ]));

        $e = new LogicException('detailed error');

        ob_start();
        ($e)($e, $conf);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertEquals('detailed error', $decoded['dialog']['error']['text']);
    }
}
