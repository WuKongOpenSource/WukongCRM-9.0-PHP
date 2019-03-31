<?php

namespace Aliyun\Test\Core\Auth;
use Aliyun\Core\Auth\Credential;
use PHPUnit\Framework\TestCase;
use Aliyun\Core\Config;

class CredentialTest extends TestCase
{
    public function setUp() {
        Config::load();
    }

	public function testCredential()
	{
		$credential = new Credential("accessKeyId", "accessSecret");
		$this->assertEquals("accessKeyId",$credential->getAccessKeyId());
		$this->assertEquals("accessSecret",$credential->getAccessSecret());
		$this->assertNotNull($credential->getRefreshDate());
		
		$dateNow = date("Y-m-d\TH:i:s\Z");
		$credential->setExpiredDate(1);
		$this->assertNotNull($credential->getExpiredDate());
		$this->assertTrue($credential->getExpiredDate() > $dateNow);	
	}
	
	
}