<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_forwarded_for_is_used_when_proxies_are_trusted(): void
    {
        $request = Request::create('http://localhost/admin/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '172.17.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ]);
        $request->setTrustedProxies(['127.0.0.1', '172.17.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $this->assertSame('203.0.113.10', $request->ip());
    }

    public function test_untrusted_proxy_keeps_gateway_ip(): void
    {
        $request = Request::create('http://localhost/admin/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '172.17.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ]);

        $request->setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);

        $this->assertSame('172.17.0.1', $request->ip());
    }
}
