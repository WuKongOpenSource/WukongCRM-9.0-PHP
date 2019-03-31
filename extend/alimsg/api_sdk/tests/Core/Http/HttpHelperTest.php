<?php

namespace Aliyun\Test\Core\Http;
use PHPUnit\Framework\TestCase;
use Aliyun\Core\Http\HttpHelper;
use Aliyun\Core\Config;

class HttpHelperTest extends TestCase
{
    function setUp()
    {
        Config::load();
    }

	public function testCurl()
	{
		$httpResponse = HttpHelper::curl("ecs.aliyuncs.com");
		$this->assertEquals(400,$httpResponse->getStatus());		
		$this->assertNotNull($httpResponse->getBody());
	}

}