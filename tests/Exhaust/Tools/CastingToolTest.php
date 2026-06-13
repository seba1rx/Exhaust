<?php

declare(strict_types=1);

namespace tests\Exhaust\Tools;

use Exhaust\Tools\CastingTool;
use PHPUnit\Framework\TestCase;

final class CastingToolTest extends TestCase
{
    // --- arrayToObject ---

    public function test_arrayToObject_returns_stdClass(): void
    {
        $result = CastingTool::arrayToObject(['foo' => 'bar']);
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function test_arrayToObject_maps_keys_as_properties(): void
    {
        $result = CastingTool::arrayToObject(['name' => 'Alice', 'age' => 30]);
        $this->assertEquals('Alice', $result->name);
        $this->assertEquals(30, $result->age);
    }

    public function test_arrayToObject_nested_array_becomes_nested_object(): void
    {
        $result = CastingTool::arrayToObject(['user' => ['id' => 1]]);
        $this->assertInstanceOf(\stdClass::class, $result->user);
        $this->assertEquals(1, $result->user->id);
    }

    // --- objectToArray ---

    public function test_objectToArray_returns_array(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $result = CastingTool::objectToArray($obj);
        $this->assertIsArray($result);
        $this->assertEquals('bar', $result['foo']);
    }

    public function test_objectToArray_with_array_input(): void
    {
        $result = CastingTool::objectToArray(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function test_objectToArray_nested(): void
    {
        $obj = new \stdClass();
        $inner = new \stdClass();
        $inner->x = 42;
        $obj->inner = $inner;
        $result = CastingTool::objectToArray($obj);
        $this->assertEquals(42, $result['inner']['x']);
    }

    // --- preferAssocArray ---

    public function test_preferAssocArray_assoc_array_returned_unchanged(): void
    {
        $input = ['key1' => 'val1', 'key2' => 'val2'];
        $result = CastingTool::preferAssocArray($input);
        $this->assertEquals($input, $result);
    }

    public function test_preferAssocArray_list_converted_to_assoc(): void
    {
        $result = CastingTool::preferAssocArray(['red', 'blue', 'green']);
        $this->assertArrayHasKey('red', $result);
        $this->assertArrayHasKey('blue', $result);
        $this->assertArrayHasKey('green', $result);
        $this->assertEquals('red', $result['red']);
    }

    public function test_preferAssocArray_object_converted_to_array(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Alice';
        $result = CastingTool::preferAssocArray($obj);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
    }

    public function test_preferAssocArray_string_wrapped_in_assoc(): void
    {
        $result = CastingTool::preferAssocArray('hello');
        $this->assertEquals(['hello' => 'hello'], $result);
    }

    public function test_preferAssocArray_int_wrapped_in_assoc(): void
    {
        $result = CastingTool::preferAssocArray(42);
        $this->assertEquals(['42' => 42], $result);
    }

    // --- castToDetectedType ---

    public function test_castToDetectedType_integer(): void
    {
        $result = CastingTool::castToDetectedType(42);
        $this->assertIsInt($result);
        $this->assertEquals(42, $result);
    }

    public function test_castToDetectedType_float_string(): void
    {
        $result = CastingTool::castToDetectedType('3.14');
        $this->assertIsFloat($result);
        $this->assertEquals(3.14, $result);
    }

    public function test_castToDetectedType_bool_true_string(): void
    {
        $result = CastingTool::castToDetectedType('TRUE');
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_castToDetectedType_bool_false_string(): void
    {
        $result = CastingTool::castToDetectedType('FALSE');
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function test_castToDetectedType_bool_case_insensitive(): void
    {
        $this->assertTrue(CastingTool::castToDetectedType('true'));
        $this->assertFalse(CastingTool::castToDetectedType('false'));
    }

    public function test_castToDetectedType_plain_string(): void
    {
        $result = CastingTool::castToDetectedType('hello');
        $this->assertIsString($result);
        $this->assertEquals('hello', $result);
    }
}
