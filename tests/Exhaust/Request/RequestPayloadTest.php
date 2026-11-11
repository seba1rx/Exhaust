<?php

declare(strict_types=1);

namespace tests\Exhaust\Request;

use Exhaust\Request\Request;
use PHPUnit\Framework\TestCase;

class RequestPayloadTest extends TestCase
{
    /**
     * Builds a Request without running the constructor, since the constructor
     * depends on an initialized App (setShouldBeIgnored reads app()->conf).
     *
     * @return Request request with an empty payload
     */
    private function makeRequest(): Request
    {
        $request = (new \ReflectionClass(Request::class))->newInstanceWithoutConstructor();
        $request->payload = new \stdClass();

        return $request;
    }

    /**
     * A JSON body with an array of objects must keep its structure instead of
     * being collapsed to the string "Array".
     *
     * @return void asserts on the payload contents
     */
    public function testNestedJsonArrayIsPreserved(): void
    {
        $json = '{"glosa": "Asiento", "lineas": ['
            . '{"cuenta_id": 1, "monto": 500, "tipo": "debe", "tags": ["a", "b"]},'
            . '{"cuenta_id": 2, "monto": 500, "tipo": "haber", "tags": []}'
            . ']}';

        $request = $this->makeRequest();
        $request->setPayload(json_decode($json, true));

        $this->assertSame('Asiento', $request->payload->glosa);
        $this->assertIsArray($request->payload->lineas);
        $this->assertCount(2, $request->payload->lineas);
        $this->assertSame(
            ['cuenta_id' => 1, 'monto' => 500, 'tipo' => 'debe', 'tags' => ['a', 'b']],
            $request->payload->lineas[0]
        );
        $this->assertSame(
            ['cuenta_id' => 2, 'monto' => 500, 'tipo' => 'haber', 'tags' => []],
            $request->payload->lineas[1]
        );
    }

    /**
     * Scalars inside nested arrays still go through the type detection.
     *
     * @return void asserts on the cast leaf values
     */
    public function testScalarsInsideNestedArraysAreCast(): void
    {
        $request = $this->makeRequest();
        $request->setPayload(['items' => [['activo' => 'TRUE', 'monto' => '12.5', 'n' => 3]]]);

        $this->assertSame(true, $request->payload->items[0]['activo']);
        $this->assertSame(12.5, $request->payload->items[0]['monto']);
        $this->assertSame(3, $request->payload->items[0]['n']);
    }

    /**
     * Objects passed through setToPayload are kept as-is.
     *
     * @return void asserts the same instance is stored
     */
    public function testObjectValueIsKeptAsIs(): void
    {
        $obj = new \stdClass();
        $obj->x = 1;

        $request = $this->makeRequest();
        $request->setToPayload('obj', $obj);

        $this->assertSame($obj, $request->payload->obj);
    }
}
