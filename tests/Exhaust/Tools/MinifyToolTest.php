<?php

declare(strict_types=1);

namespace tests\Exhaust\Tools;

use Exhaust\Tools\MinifyTool;
use PHPUnit\Framework\TestCase;

final class MinifyToolTest extends TestCase
{
    // --- html() / minify() ---

    public function test_html_removes_newlines(): void
    {
        $html = "<div>\n    <p>Hello</p>\n</div>";
        $result = MinifyTool::html($html);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function test_html_removes_extra_whitespace_between_tags(): void
    {
        $html = "<div>   <p>text</p>   </div>";
        $result = MinifyTool::html($html);
        $this->assertStringContainsString('<div><p>', $result);
    }

    public function test_html_removes_html_comments(): void
    {
        $html = "<div><!-- this is a comment --><p>text</p></div>";
        $result = MinifyTool::html($html);
        $this->assertStringNotContainsString('<!--', $result);
        $this->assertStringNotContainsString('-->', $result);
        $this->assertStringContainsString('<p>text</p>', $result);
    }

    public function test_html_preserves_pre_tags_in_output(): void
    {
        // removeNewLinesAndWhiteSpaces() runs before pre-block extraction, so
        // whitespace inside <pre> is collapsed — but the <pre> tags themselves survive.
        $html = "<div><pre>code block</pre></div>";
        $result = MinifyTool::html($html);
        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('</pre>', $result);
        $this->assertStringContainsString('code block', $result);
    }

    public function test_html_merges_multiple_script_tags(): void
    {
        $html = "<html><body><script>var a = 1;</script><script>var b = 2;</script></body></html>";
        $result = MinifyTool::html($html);
        // Multiple inline scripts are merged into one
        $scriptCount = substr_count($result, '<script>');
        $this->assertEquals(1, $scriptCount);
    }

    public function test_html_does_not_touch_script_with_src(): void
    {
        $html = '<html><body><script src="app.js"></script></body></html>';
        $result = MinifyTool::html($html);
        $this->assertStringContainsString('src="app.js"', $result);
    }

    // --- js() ---

    public function test_js_removes_single_line_comments(): void
    {
        $js = "var a = 1; // this is a comment\nvar b = 2;";
        $result = MinifyTool::js($js);
        $this->assertStringNotContainsString('//', $result);
    }

    public function test_js_removes_block_comments(): void
    {
        $js = "/* block comment */\nvar a = 1;";
        $result = MinifyTool::js($js);
        $this->assertStringNotContainsString('/*', $result);
        $this->assertStringContainsString('var a = 1;', $result);
    }

    public function test_js_removes_trailing_commas_before_closing_brace(): void
    {
        $js = 'var obj = {"key": "value",}';
        $result = MinifyTool::js($js);
        $this->assertStringNotContainsString(',}', $result);
    }

    // --- minify() ---

    public function test_minify_collapses_tabs_to_single_space(): void
    {
        $content = "a\t\t\tb";
        $result = MinifyTool::minify($content);
        $this->assertStringNotContainsString("\t", $result);
    }

    public function test_minify_empty_string_returns_empty(): void
    {
        $result = MinifyTool::minify('');
        $this->assertEquals('', $result);
    }

    public function test_minify_strips_space_after_equals_before_quote(): void
    {
        $html = '<div class= "foo"></div>';
        $result = MinifyTool::minify($html);
        $this->assertStringContainsString('class="foo"', $result);
    }
}
