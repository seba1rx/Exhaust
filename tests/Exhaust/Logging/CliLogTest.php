<?php

declare(strict_types=1);

namespace tests\Exhaust\Logging;

use Exhaust\Logging\CliLog;
use PHPUnit\Framework\TestCase;

final class CliLogTest extends TestCase
{
    private function isAnsiColored(string $output): bool
    {
        return str_starts_with($output, "\033[");
    }

    // --- colorLog ---

    public function test_colorLog_info_uses_cyan(): void
    {
        $output = CliLog::colorLog('msg', 'i');
        $this->assertStringContainsString("\033[36m", $output);
        $this->assertStringContainsString('msg', $output);
    }

    public function test_colorLog_error_uses_red(): void
    {
        $output = CliLog::colorLog('err', 'e');
        $this->assertStringContainsString("\033[31m", $output);
    }

    public function test_colorLog_success_uses_green(): void
    {
        $output = CliLog::colorLog('ok', 's');
        $this->assertStringContainsString("\033[32m", $output);
    }

    public function test_colorLog_warning_uses_yellow(): void
    {
        $output = CliLog::colorLog('warn', 'w');
        $this->assertStringContainsString("\033[33m", $output);
    }

    public function test_colorLog_unknown_type_uses_default(): void
    {
        $output = CliLog::colorLog('msg', 'z');
        // Unknown type falls back to 0 (no color / reset)
        $this->assertStringContainsString("\033[0m", $output);
    }

    public function test_colorLog_ends_with_reset_and_newline(): void
    {
        $output = CliLog::colorLog('msg', 'i');
        $this->assertStringEndsWith("\033[0m\n", $output);
    }

    // --- info ---

    public function test_info_contains_message(): void
    {
        $output = CliLog::info('info message');
        $this->assertStringContainsString('info message', $output);
        $this->assertTrue($this->isAnsiColored($output));
    }

    public function test_info_uses_cyan_color_code(): void
    {
        $output = CliLog::info('x');
        $this->assertStringContainsString("\033[36m", $output);
    }

    // --- warning ---

    public function test_warning_contains_message(): void
    {
        $output = CliLog::warning('watch out');
        $this->assertStringContainsString('watch out', $output);
    }

    public function test_warning_uses_yellow_color_code(): void
    {
        $output = CliLog::warning('x');
        $this->assertStringContainsString("\033[33m", $output);
    }

    // --- success ---

    public function test_success_contains_message(): void
    {
        $output = CliLog::success('all done');
        $this->assertStringContainsString('all done', $output);
    }

    public function test_success_uses_green_color_code(): void
    {
        $output = CliLog::success('x');
        $this->assertStringContainsString("\033[32m", $output);
    }

    // --- error ---

    public function test_error_contains_message(): void
    {
        $output = CliLog::error('something broke');
        $this->assertStringContainsString('something broke', $output);
    }

    public function test_error_uses_red_color_code(): void
    {
        $output = CliLog::error('x');
        $this->assertStringContainsString("\033[31m", $output);
    }

    // --- return type ---

    public function test_all_methods_return_string(): void
    {
        $this->assertIsString(CliLog::info('x'));
        $this->assertIsString(CliLog::warning('x'));
        $this->assertIsString(CliLog::success('x'));
        $this->assertIsString(CliLog::error('x'));
        $this->assertIsString(CliLog::colorLog('x', 'i'));
    }
}
