<?php
namespace smpp;

/**
 * Primitive class for encapsulating PDUs
 * @author hd@onlinecity.dk
 */
class Pdu
{
	public $id;
	public $status;
	public $sequence;
	public $body;
	
	/**
	 * Create new generic PDU object
	 */
	public function __construct (int $id, int $status, int $sequence, ?string $body)
	{
		$this->id = $id;
		$this->status = $status;
		$this->sequence = $sequence;
		$this->body = $body;
	}
}