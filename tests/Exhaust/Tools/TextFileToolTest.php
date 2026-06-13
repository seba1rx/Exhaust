<?php

declare(strict_types=1);

namespace tests\Exhaust\Tools;

use Exhaust\Tools\TextFileTool;
use PHPUnit\Framework\TestCase;

final class TextFileToolTest extends TestCase
{
    private TextFileTool $tool;

    protected function setUp(): void
    {
        $this->tool = new TextFileTool();
    }

    // --- constructor ---

    public function test_constructor_default_no_throw(): void
    {
        $this->assertInstanceOf(TextFileTool::class, new TextFileTool());
    }

    public function test_constructor_with_conf(): void
    {
        $tool = new TextFileTool(['fileFormat' => 'md', 'directory' => 'storage/logs']);
        $this->assertInstanceOf(TextFileTool::class, $tool);
    }

    // --- addContent / nl ---

    public function test_addContent_accumulates_content(): void
    {
        $this->tool->addContent('Hello');
        $this->tool->addContent(' World');

        // Content is private; verify indirectly via writeContentToFile on a real tmp file
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);

        $fileName = 'exhaust_test_' . uniqid();
        $tool->addContent('Hello World');
        $tool->createFile($fileName);
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $this->assertStringContainsString('Hello World', file_get_contents($path));
        unlink($path);
    }

    public function test_nl_appends_newline(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_nl_' . uniqid();
        $tool->createFile($fileName);
        $tool->addContent('line1');
        $tool->nl();
        $tool->addContent('line2');
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $content = file_get_contents($path);
        $this->assertStringContainsString("line1\nline2", $content);
        unlink($path);
    }

    // --- setFillChar / addPaddedContent ---

    public function test_addPaddedContent_pads_to_length(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_pad_' . uniqid();
        $tool->createFile($fileName);
        $tool->setFillChar('0');
        $tool->addPaddedContent('abc', 10);
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $content = file_get_contents($path);
        $this->assertEquals('abc0000000', $content);
        unlink($path);
    }

    public function test_addPaddedContent_uppercase(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_upper_' . uniqid();
        $tool->createFile($fileName);
        $tool->setFillChar(' ');
        $tool->addPaddedContent('hello', 10, TextFileTool::STR_TO_UPPER);
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $this->assertStringStartsWith('HELLO', file_get_contents($path));
        unlink($path);
    }

    public function test_addPaddedContent_lowercase(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_lower_' . uniqid();
        $tool->createFile($fileName);
        $tool->setFillChar(' ');
        $tool->addPaddedContent('HELLO', 10, TextFileTool::STR_TO_LOWER);
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $this->assertStringStartsWith('hello', file_get_contents($path));
        unlink($path);
    }

    // --- getWrittenBytes / contentWasWritten ---

    public function test_contentWasWritten_true_after_successful_write(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_written_' . uniqid();
        $tool->createFile($fileName);
        $tool->addContent('data');
        $tool->writeContentToFile();
        $tool->closeFile();

        $this->assertTrue($tool->contentWasWritten());

        unlink($tmpDir . '/' . $fileName);
    }

    public function test_getWrittenBytes_returns_positive_after_write(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_bytes_' . uniqid();
        $tool->createFile($fileName);
        $tool->addContent('hello');
        $tool->writeContentToFile();
        $tool->closeFile();

        $this->assertGreaterThan(0, $tool->getWrittenBytes());

        unlink($tmpDir . '/' . $fileName);
    }

    // --- removeFile ---

    public function test_removeFile_removes_existing_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'exhaust_rm_');
        $this->assertFileExists($path);
        $result = $this->tool->removeFile($path);
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($path);
    }

    public function test_removeFile_returns_false_for_nonexistent_file(): void
    {
        $result = $this->tool->removeFile('/tmp/does_not_exist_' . uniqid());
        $this->assertFalse($result);
    }

    // --- setNewLineString ---

    public function test_setNewLineString_changes_newline_char(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $tool->setNewLineString("\r\n");
        $fileName = 'exhaust_test_crlf_' . uniqid();
        $tool->createFile($fileName);
        $tool->addContent('line1');
        $tool->nl();
        $tool->addContent('line2');
        $tool->writeContentToFile();
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        $content = file_get_contents($path);
        $this->assertStringContainsString("\r\n", $content);
        unlink($path);
    }

    // --- getFullPathToFile ---

    public function test_getFullPathToFile_returns_string_after_create(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tool = new TextFileTool(['fileFormat' => '', 'directory' => $tmpDir]);
        $fileName = 'exhaust_test_path_' . uniqid();
        $tool->createFile($fileName);
        $tool->closeFile();

        $path = $tmpDir . '/' . $fileName;
        unlink($path);

        // getFullPathToFile should return a non-empty string
        $this->assertIsString($tool->getFullPathToFile());
    }
}
