<?php

declare(strict_types=1);

namespace tests\Exhaust\Request;

use Exhaust\Request\RequestType;
use PHPUnit\Framework\TestCase;

final class RequestTypeTest extends TestCase
{
    public function test_cases_exist(): void
    {
        $cases = RequestType::cases();
        $names = array_column($cases, 'name');

        $this->assertContains('Navigation', $names);
        $this->assertContains('XHR', $names);
        $this->assertContains('Fetch', $names);
    }

    public function test_navigation_value(): void
    {
        $this->assertEquals('navigation', RequestType::Navigation->value);
    }

    public function test_xhr_value(): void
    {
        $this->assertEquals('xhr', RequestType::XHR->value);
    }

    public function test_fetch_value(): void
    {
        $this->assertEquals('fetch', RequestType::Fetch->value);
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(RequestType::Navigation, RequestType::from('navigation'));
        $this->assertSame(RequestType::XHR, RequestType::from('xhr'));
        $this->assertSame(RequestType::Fetch, RequestType::from('fetch'));
    }

    public function test_tryFrom_invalid_value_returns_null(): void
    {
        $this->assertNull(RequestType::tryFrom('invalid'));
    }

    public function test_navigation_is_not_xhr(): void
    {
        $this->assertNotSame(RequestType::Navigation, RequestType::XHR);
    }
}
