<?php
namespace smpp;

/**
 * Primitive type to represent SMSes
 * @author hd@onlinecity.dk
 */
class Sms extends Pdu
{
	public $service_type;
	public $source;
	public $destination;
	public $esmClass;
	public $protocolId;
	public $priorityFlag;
	public $registeredDelivery;
	public $dataCoding;
	public $message;
	public $tags;
	
	// Unused in deliver_sm
	public $scheduleDeliveryTime;
	public $validityPeriod;
	public $smDefaultMsgId;
	public $replaceIfPresentFlag;
	
	/**
	 * Construct a new SMS
	 */
	public function __construct (int $id, int $status, int $sequence, string $body, string $service_type, Address $source, Address $destination, int $esmClass, int $protocolId, int $priorityFlag, int $registeredDelivery, int $dataCoding, string $message, ?array $tags = null, ?string $scheduleDeliveryTime = null, ?string $validityPeriod = null, ?int $smDefaultMsgId = null, ?int $replaceIfPresentFlag = null)
	{
		parent::__construct($id, $status, $sequence, $body);
		
		$this->service_type = $service_type;
		$this->source = $source;
		$this->destination = $destination;
		$this->esmClass = $esmClass;
		$this->protocolId = $protocolId;
		$this->priorityFlag = $priorityFlag;
		$this->registeredDelivery = $registeredDelivery;
		$this->dataCoding = $dataCoding;
		$this->message = $message;
		$this->tags = $tags;
		$this->scheduleDeliveryTime = $scheduleDeliveryTime;
		$this->validityPeriod = $validityPeriod;
		$this->smDefaultMsgId = $smDefaultMsgId;
		$this->replaceIfPresentFlag = $replaceIfPresentFlag;
	}
}