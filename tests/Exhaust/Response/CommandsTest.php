<?php

declare(strict_types=1);

namespace tests\Exhaust\Response;

use Exhaust\Response\Commands;
use Exhaust\Exceptions\LogicException;
use PHPUnit\Framework\TestCase;

final class CommandsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static $commands between tests via reflection
        $prop = new \ReflectionProperty(Commands::class, 'commands');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    // --- echo ---

    public function test_echo_stores_html(): void
    {
        Commands::echo('<div>Hello</div>');
        $this->assertEquals('<div>Hello</div>', Commands::all()['echo']);
    }

    // --- html ---

    public function test_html_stores_content_by_node_id(): void
    {
        Commands::html('main-content', '<p>Body</p>');
        $cmds = Commands::all();
        $this->assertArrayHasKey('html', $cmds);
        $this->assertEquals('<p>Body</p>', $cmds['html']['main-content']);
    }

    public function test_html_accumulates_multiple_nodes(): void
    {
        Commands::html('header', '<h1>Title</h1>');
        Commands::html('footer', '<footer>End</footer>');
        $cmds = Commands::all();
        $this->assertCount(2, $cmds['html']);
    }

    // --- assignValue ---

    public function test_assignValue_stores_variable(): void
    {
        Commands::assignValue('myVar', 42);
        $cmds = Commands::all();
        $this->assertArrayHasKey('assignValue', $cmds);
        $this->assertEquals(42, $cmds['assignValue']['myVar']);
    }

    // --- console_log ---

    public function test_console_log_stores_value(): void
    {
        Commands::console_log('debug info');
        $this->assertEquals('debug info', Commands::all()['console_log']);
    }

    // --- log ---

    public function test_log_stores_typed_entry(): void
    {
        Commands::log('info', 'Something happened');
        $cmds = Commands::all();
        $this->assertArrayHasKey('log', $cmds);
        $this->assertEquals('Something happened', $cmds['log']['info']['text']);
    }

    public function test_log_with_details(): void
    {
        Commands::log('error', 'Failed', ['code' => 500]);
        $cmds = Commands::all();
        $this->assertEquals(['code' => 500], $cmds['log']['error']['details']);
    }

    // --- dialog ---

    public function test_dialog_stores_typed_dialog(): void
    {
        Commands::dialog('success', ['title' => 'Done', 'text' => 'All good']);
        $cmds = Commands::all();
        $this->assertArrayHasKey('dialog', $cmds);
        $this->assertEquals('Done', $cmds['dialog']['success']['title']);
    }

    // --- script ---

    public function test_script_stores_minified_js(): void
    {
        Commands::script('var x = 1;');
        $this->assertArrayHasKey('script', Commands::all());
    }

    // --- apiResponse ---

    public function test_apiResponse_stores_object(): void
    {
        Commands::apiResponse(['status' => 'ok', 'count' => 3]);
        $cmds = Commands::all();
        $this->assertArrayHasKey('api', $cmds);
        $this->assertEquals('ok', $cmds['api']->status);
    }

    // --- all / getCommands ---

    public function test_all_and_getCommands_return_same_data(): void
    {
        Commands::echo('test');
        $this->assertEquals(Commands::all(), Commands::getCommands());
    }

    // --- validateCommands ---

    public function test_validateCommands_accepts_valid_echo(): void
    {
        $data = ['echo' => '<html></html>'];
        $result = Commands::validateCommands($data);
        $this->assertEquals($data, $result);
    }

    public function test_validateCommands_accepts_valid_html(): void
    {
        $data = ['html' => ['node' => '<p>content</p>']];
        $result = Commands::validateCommands($data);
        $this->assertEquals($data, $result);
    }

    public function test_validateCommands_accepts_valid_log(): void
    {
        $data = ['log' => ['info' => ['text' => 'msg']]];
        $result = Commands::validateCommands($data);
        $this->assertEquals($data, $result);
    }

    public function test_validateCommands_accepts_valid_dialog(): void
    {
        $data = ['dialog' => ['success' => ['title' => 'Ok', 'text' => 'Done']]];
        $result = Commands::validateCommands($data);
        $this->assertEquals($data, $result);
    }

    public function test_validateCommands_throws_on_unknown_command(): void
    {
        $this->expectException(\LogicException::class);
        Commands::validateCommands(['unknownCmd' => 'value']);
    }

    public function test_validateCommands_throws_on_empty_echo(): void
    {
        $this->expectException(\Exception::class);
        Commands::validateCommands(['echo' => '']);
    }

    public function test_validateCommands_throws_on_empty_html_array(): void
    {
        $this->expectException(\Exception::class);
        Commands::validateCommands(['html' => []]);
    }

    public function test_validateCommands_throws_on_invalid_log_type(): void
    {
        $this->expectException(\Exception::class);
        Commands::validateCommands(['log' => ['invalid_type' => ['text' => 'msg']]]);
    }

    public function test_validateCommands_throws_on_invalid_dialog_type(): void
    {
        $this->expectException(\Exception::class);
        Commands::validateCommands(['dialog' => ['invalid' => ['title' => 'T', 'text' => 'B']]]);
    }

    // --- dialogBuilder ---

    public function test_dialogBuilder_builds_basic_dialog(): void
    {
        $dialog = Commands::dialogBuilder('success', 'Title', 'Body text');
        $this->assertEquals('success', $dialog['icon']);
        $this->assertEquals('Title', $dialog['title']);
        $this->assertEquals('Body text', $dialog['text']);
    }

    public function test_dialogBuilder_with_confirm_button(): void
    {
        $dialog = Commands::dialogBuilder(
            icon: 'info',
            title: 'Confirm?',
            btn_confirm: ['text' => 'Yes']
        );
        $this->assertEquals('Yes', $dialog['buttons']['confirm']['text']);
    }

    public function test_dialogBuilder_confirm_button_throws_without_text(): void
    {
        $this->expectException(LogicException::class);
        Commands::dialogBuilder(
            icon: 'info',
            btn_confirm: ['class' => 'btn-primary']
        );
    }

    public function test_dialogBuilder_with_timer(): void
    {
        $dialog = Commands::dialogBuilder(
            icon: 'success',
            title: 'Auto close',
            timer: ['time' => 3000]
        );
        $this->assertArrayHasKey('timer', $dialog);
    }

    public function test_dialogBuilder_show_loading(): void
    {
        $dialog = Commands::dialogBuilder(icon: 'info', showLoading: true);
        $this->assertTrue($dialog['showLoading']);
    }
}
