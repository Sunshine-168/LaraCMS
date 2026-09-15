<?php

namespace Tests\Unit;

use App\Support\IpLocation;
use Tests\TestCase;

class IpLocationTest extends TestCase
{
    public function test_labels_loopback_docker_lan_and_docs_net(): void
    {
        $this->assertSame('本机', IpLocation::label('127.0.0.1'));
        $this->assertSame('本机', IpLocation::label('::1'));
        $this->assertSame('内网（Docker）', IpLocation::label('172.17.0.1'));
        $this->assertSame('内网', IpLocation::label('192.168.1.8'));
        $this->assertSame('内网', IpLocation::label('10.0.0.4'));
        $this->assertSame('链路本地', IpLocation::label('169.254.1.1'));
        $this->assertSame('保留地址', IpLocation::label('203.0.113.10'));
        $this->assertSame('公网', IpLocation::label('8.8.8.8'));
        $this->assertSame('', IpLocation::label('not-an-ip'));
    }
}
