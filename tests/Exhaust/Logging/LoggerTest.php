<?php

declare(strict_types=1);

namespace tests\Exhaust\Logging;

use Exhaust\Logging\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/** Concrete spy logger — typed return avoids "undefined property" IDE warnings. */
class SpyLogger implements LoggerInterface
{
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }

    public function emergency(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert(string|\Stringable $message, array $context = []): void     { $this->log(LogLevel::ALERT,     $message, $context); }
    public function critical(string|\Stringable $message, array $context = []): void  { $this->log(LogLevel::CRITICAL,  $message, $context); }
    public function error(string|\Stringable $message, array $context = []): void     { $this->log(LogLevel::ERROR,     $message, $context); }
    public function warning(string|\Stringable $message, array $context = []): void   { $this->log(LogLevel::WARNING,   $message, $context); }
    public function notice(string|\Stringable $message, array $context = []): void    { $this->log(LogLevel::NOTICE,    $message, $context); }
    public function info(string|\Stringable $message, array $context = []): void      { $this->log(LogLevel::INFO,      $message, $context); }
    public function debug(string|\Stringable $message, array $context = []): void     { $this->log(LogLevel::DEBUG,     $message, $context); }
}

final class LoggerTest extends TestCase
{
    private SpyLogger $spy;

    protected function setUp(): void
    {
        $this->spy = new SpyLogger();

        // Reset static logger between tests
        $prop = new \ReflectionProperty(Logger::class, 'logger');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_set_assigns_logger(): void
    {
        Logger::set($this->spy);

        $prop = new \ReflectionProperty(Logger::class, 'logger');
        $prop->setAccessible(true);
        $this->assertSame($this->spy, $prop->getValue(null));
    }

    public function test_log_calls_underlying_psr_logger(): void
    {
        Logger::set($this->spy);
        Logger::log(new \stdClass(), 'test message');

        $this->assertCount(1, $this->spy->records);
        $this->assertEquals(LogLevel::DEBUG, $this->spy->records[0]['level']);
    }

    public function test_log_with_object_includes_class_and_pid_in_message(): void
    {
        Logger::set($this->spy);
        Logger::log(new \stdClass(), 'hello');

        $decoded = json_decode($this->spy->records[0]['message'], true);
        $this->assertStringContainsString('stdClass', $decoded['message']);
        $this->assertStringContainsString('PID', $decoded['message']);
    }

    public function test_log_encodes_message_as_json(): void
    {
        Logger::set($this->spy);
        Logger::log(new \stdClass(), 'json test');

        $this->assertJson($this->spy->records[0]['message']);
    }

    public function test_alert_delegates_to_log(): void
    {
        Logger::set($this->spy);
        Logger::alert(new \stdClass(), 'alert msg');

        $this->assertCount(1, $this->spy->records);
    }

    public function test_log_passes_context_array(): void
    {
        Logger::set($this->spy);
        Logger::log(new \stdClass(), 'with context', ['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $this->spy->records[0]['context']);
    }

    public function test_log_with_string_throws_type_error_in_php84(): void
    {
        // KNOWN BUG: Logger.php:44 uses $object::class which throws TypeError in
        // PHP 8.4 when $object is a string. The parameter is untyped so strings
        // are accepted at call-site but ::class on a string fails at runtime.
        Logger::set($this->spy);

        $this->expectException(\TypeError::class);
        Logger::log('SomeClass', 'this fails in PHP 8.4');
    }
}
