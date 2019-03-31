<?php

namespace Aliyun\Api\Msg\Request\V20170525;
use Aliyun\Core\RpcAcsRequest;

class QueryTokenForMnsQueueRequest extends RpcAcsRequest
{
	function  __construct()
	{
		parent::__construct("Dybaseapi", "2017-05-25", "QueryTokenForMnsQueue");
	}

	private  $ownerId;

	private  $resourceOwnerId;

	private  $resourceOwnerAccount;

	private  $messageType;

	public function getOwnerId() {
		return $this->ownerId;
	}

	public function setOwnerId($ownerId) {
		$this->ownerId = $ownerId;
		$this->queryParameters["OwnerId"]=$ownerId;
	}

	public function getResourceOwnerId() {
		return $this->resourceOwnerId;
	}

	public function setResourceOwnerId($resourceOwnerId) {
		$this->resourceOwnerId = $resourceOwnerId;
		$this->queryParameters["ResourceOwnerId"]=$resourceOwnerId;
	}

	public function getResourceOwnerAccount() {
		return $this->resourceOwnerAccount;
	}

	public function setResourceOwnerAccount($resourceOwnerAccount) {
		$this->resourceOwnerAccount = $resourceOwnerAccount;
		$this->queryParameters["ResourceOwnerAccount"]=$resourceOwnerAccount;
	}

	public function getMessageType() {
		return $this->messageType;
	}

	public function setMessageType($messageType) {
		$this->messageType = $messageType;
		$this->queryParameters["MessageType"]=$messageType;
	}
	
}