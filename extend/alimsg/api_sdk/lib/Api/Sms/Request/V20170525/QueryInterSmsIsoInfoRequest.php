<?php

namespace Aliyun\Api\Sms\Request\V20170525;

use Aliyun\Core\RpcAcsRequest;

class QueryInterSmsIsoInfoRequest extends RpcAcsRequest
{
	function  __construct()
	{
		parent::__construct("Dysmsapi", "2017-05-25", "QueryInterSmsIsoInfo");
		$this->setMethod("POST");
	}

	private  $resourceOwnerAccount;

	private  $countryName;

	private  $resourceOwnerId;

	private  $ownerId;

	public function getResourceOwnerAccount() {
		return $this->resourceOwnerAccount;
	}

	public function setResourceOwnerAccount($resourceOwnerAccount) {
		$this->resourceOwnerAccount = $resourceOwnerAccount;
		$this->queryParameters["ResourceOwnerAccount"]=$resourceOwnerAccount;
	}

	public function getCountryName() {
		return $this->countryName;
	}

	public function setCountryName($countryName) {
		$this->countryName = $countryName;
		$this->queryParameters["CountryName"]=$countryName;
	}

	public function getResourceOwnerId() {
		return $this->resourceOwnerId;
	}

	public function setResourceOwnerId($resourceOwnerId) {
		$this->resourceOwnerId = $resourceOwnerId;
		$this->queryParameters["ResourceOwnerId"]=$resourceOwnerId;
	}

	public function getOwnerId() {
		return $this->ownerId;
	}

	public function setOwnerId($ownerId) {
		$this->ownerId = $ownerId;
		$this->queryParameters["OwnerId"]=$ownerId;
	}
	
}