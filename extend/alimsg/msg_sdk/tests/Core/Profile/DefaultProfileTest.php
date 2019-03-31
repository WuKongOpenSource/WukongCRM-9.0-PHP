<?php

namespace Aliyun\Test\Core\Profile;

use PHPUnit\Framework\TestCase;
use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;


class DefaultProfileTest extends TestCase
{

    public function setUp(){
        Config::load();
    }

	public function testGetProfile()
	{
		$profile = DefaultProfile::getProfile("cn-hangzhou", "accessId", "accessSecret");
		$this->assertEquals("cn-hangzhou",$profile->getRegionId());
		$this->assertEquals("accessId",$profile->getCredential()->getAccessKeyId());
		$this->assertEquals("accessSecret",$profile->getCredential()->getAccessSecret());
	}
	
	public function testAddEndpoint()
	{
		$profile = DefaultProfile::getProfile("cn-hangzhou", "accessId", "accessSecret");
		$profile->addEndpoint("cn-hangzhou", "cn-hangzhou", "TestProduct", "testproduct.aliyuncs.com");
		$endpoints = $profile->getEndpoints();
		foreach ($endpoints as $key => $endpoint)
		{
			if("cn-hangzhou" == $endpoint->getName())
			{
				$regionIds = $endpoint->getRegionIds();
				$this->assertContains("cn-hangzhou",$regionIds);
				
				$productDomains= $endpoint->getProductDomains();
				$this->assertNotNull($productDomains);
				$productDomain = $this->getProductDomain($productDomains);
				$this->assertNotNull($productDomain);
				$this->assertEquals("TestProduct",$productDomain->getProductName());
				$this->assertEquals("testproduct.aliyuncs.com",$productDomain->getDomainName());
			}
		}
	}
	
	private function getProductDomain($productDomains)
	{
		foreach ($productDomains as $productDomain)
		{
			if($productDomain->getProductName() == "TestProduct")
			{
				return $productDomain;
			}
		}
		return null;
	}
		
}