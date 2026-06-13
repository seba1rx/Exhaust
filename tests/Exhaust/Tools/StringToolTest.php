<?php

declare(strict_types=1);

namespace tests\Exhaust\Tools;

use Exhaust\Tools\StringTool;
use PHPUnit\Framework\TestCase;

final class StringToolTest extends TestCase
{
    // --- getStringAfterFirst ---

    public function test_getStringAfterFirst_returns_substring_after_first_needle(): void
    {
        $this->assertEquals('hoho', StringTool::getStringAfterFirst('o', 'hohoho'));
    }

    public function test_getStringAfterFirst_needle_at_start(): void
    {
        $this->assertEquals('bar', StringTool::getStringAfterFirst('/', '/bar'));
    }

    public function test_getStringAfterFirst_returns_false_when_needle_not_found(): void
    {
        $this->assertFalse(StringTool::getStringAfterFirst('z', 'hohoho'));
    }

    // --- getStringAfterLast ---

    public function test_getStringAfterLast_returns_substring_after_last_needle(): void
    {
        $this->assertEquals('world', StringTool::getStringAfterLast('/', 'foo/bar/world'));
    }

    public function test_getStringAfterLast_returns_false_when_needle_not_found(): void
    {
        $this->assertFalse(StringTool::getStringAfterLast('z', 'hohoho'));
    }

    public function test_getStringAfterLast_single_occurrence(): void
    {
        $this->assertEquals('bar', StringTool::getStringAfterLast('/', 'foo/bar'));
    }

    // --- getStringBeforeFirst ---

    public function test_getStringBeforeFirst_returns_part_before_first_needle(): void
    {
        $this->assertEquals('foo', StringTool::getStringBeforeFirst('/', 'foo/bar/baz'));
    }

    public function test_getStringBeforeFirst_needle_at_end(): void
    {
        $this->assertEquals('foo', StringTool::getStringBeforeFirst('/', 'foo/'));
    }

    // --- getStringBeforeLast ---

    public function test_getStringBeforeLast_returns_part_before_last_needle(): void
    {
        $this->assertEquals('foo/bar', StringTool::getStringBeforeLast('/', 'foo/bar/baz'));
    }

    public function test_getStringBeforeLast_returns_false_when_needle_not_found(): void
    {
        $this->assertFalse(StringTool::getStringBeforeLast('z', 'hohoho'));
    }

    // --- getStringBetweenFirstAndLast ---

    public function test_getStringBetweenFirstAndLast_basic(): void
    {
        $result = StringTool::getStringBetweenFirstAndLast('{', '}', '{hello world}');
        $this->assertEquals('hello world', $result);
    }

    public function test_getStringBetweenFirstAndLast_nested(): void
    {
        $result = StringTool::getStringBetweenFirstAndLast('{', '}', '{outer {inner} text}');
        $this->assertEquals('outer {inner} text', $result);
    }

    public function test_getStringBetweenFirstAndLast_returns_false_when_no_first_needle(): void
    {
        $this->assertFalse(StringTool::getStringBetweenFirstAndLast('[', ']', 'no brackets here'));
    }

    // --- getStringBetweenLastAndFirst ---

    public function test_getStringBetweenLastAndFirst_basic(): void
    {
        $result = StringTool::getStringBetweenLastAndFirst('/', '.', 'path/to/file.txt');
        $this->assertEquals('file', $result);
    }

    public function test_getStringBetweenLastAndFirst_returns_false_when_no_first_needle(): void
    {
        // 'only-dashes-here.txt' contains no 'z', so getStringAfterLast returns false
        $this->assertFalse(StringTool::getStringBetweenLastAndFirst('z', '.', 'only-dashes-here.txt'));
    }

    // --- isWrappedBetween ---

    public function test_isWrappedBetween_same_char(): void
    {
        $this->assertTrue(StringTool::isWrappedBetween('"hello"', '"'));
    }

    public function test_isWrappedBetween_different_chars(): void
    {
        $this->assertTrue(StringTool::isWrappedBetween('{name}', '{', '}'));
    }

    public function test_isWrappedBetween_returns_false_when_not_wrapped(): void
    {
        $this->assertFalse(StringTool::isWrappedBetween('hello', '{', '}'));
    }

    public function test_isWrappedBetween_only_opening(): void
    {
        $this->assertFalse(StringTool::isWrappedBetween('{hello', '{', '}'));
    }

    // --- generateRandomSerial ---

    public function test_generateRandomSerial_default_length_is_10(): void
    {
        $serial = StringTool::generateRandomSerial();
        $this->assertEquals(10, \strlen($serial));
    }

    public function test_generateRandomSerial_custom_length(): void
    {
        $serial = StringTool::generateRandomSerial(20);
        $this->assertEquals(20, \strlen($serial));
    }

    public function test_generateRandomSerial_produces_different_values(): void
    {
        $a = StringTool::generateRandomSerial();
        $b = StringTool::generateRandomSerial();
        // Statistically almost impossible to be equal
        $this->assertNotEquals($a, $b);
    }

    public function test_generateRandomSerial_uses_alphanumeric_chars(): void
    {
        $serial = StringTool::generateRandomSerial(100);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $serial);
    }

    // --- checkEmail ---

    public function test_checkEmail_valid_email_returns_lowercase(): void
    {
        $result = StringTool::checkEmail('User@Example.COM');
        $this->assertEquals('user@example.com', $result);
    }

    public function test_checkEmail_invalid_email_returns_false(): void
    {
        $this->assertFalse(StringTool::checkEmail('not-an-email'));
    }

    public function test_checkEmail_missing_tld_returns_false(): void
    {
        $this->assertFalse(StringTool::checkEmail('user@domain'));
    }

    public function test_checkEmail_valid_subdomain(): void
    {
        $result = StringTool::checkEmail('user@mail.example.com');
        $this->assertIsString($result);
    }
}
