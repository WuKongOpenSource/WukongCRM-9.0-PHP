<?php

namespace Aliyun\Test\Core\Profile;
use PHPUnit\Framework\TestCase;
use Aliyun\Core\Regions\EndpointProvider;
use Aliyun\Core\Config;

class EndpointProviderTest extends TestCase
{
    public function setUp() {
        Config::load();
    }

	public function testFindProductDomain()
	{
		$this->assertEquals("ecs-cn-hangzhou.aliyuncs.com",EndpointProvider::findProductDomain("cn-hangzhou", "Ecs"));
	}
	
}