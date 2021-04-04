<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class ChargeDischargeManagement extends Command{
	public int $chargeVoltageLimit;
	public int $dischargeVoltageLimit;
	public int $chargeCurrentLimit;
	public int $dischargeCurrentLimit;
	
	public int $chargeStatus;
	public bool $chargeEnabled; //If false, battery wants you to not charge.
	public bool $dischargeEnabled; //If false, battery wants you to not discharge.
	public bool $chargeImmediately1; //If true, battery wants to be immediately charged
	public bool $chargeImmediately2; //If true, battery wants to be immediately charged, but less urgent?
	public bool $fullChargeRequest; //If true, battery wants to be charged above 97% in order to perform balancing and calibration.
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::CHARGE_DISCHG_MANAGEMENT, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		$this->chargeVoltageLimit = $data->getDec(2);
		$this->dischargeVoltageLimit = $data->getDec(2);
		$this->chargeCurrentLimit = HexHelper::signedHexToDec($data->getHex(2))*100;
		$this->dischargeCurrentLimit = HexHelper::signedHexToDec($data->getHex(2))*100;
		
		$this->chargeStatus = $data->getDec();
		$this->chargeEnabled = (bool) ($this->chargeStatus & 0b10000000);
		$this->dischargeEnabled = (bool) ($this->chargeStatus & 0b01000000);
		$this->chargeImmediately1 = (bool) ($this->chargeStatus & 0b00100000);
		$this->chargeImmediately2 = (bool) ($this->chargeStatus & 0b00010000);
		$this->fullChargeRequest = (bool) ($this->chargeStatus & 0b00001000);
	}
	
	public function infoString(): string{
		return  parent::infoString().PHP_EOL.
			"ChargeDischargeManagement:".PHP_EOL.
			"ChargeVoltageLimit (mV): ".$this->chargeVoltageLimit.PHP_EOL.
			"DischargeVoltageLimit (mV): ".$this->dischargeVoltageLimit.PHP_EOL.
			"ChargeCurrentLimit (A): ".($this->chargeCurrentLimit/1000).PHP_EOL.
			"DischargeCurrentLimit (A): ".($this->dischargeCurrentLimit/1000).PHP_EOL.
			"chargeStatus:".decbin($this->chargeStatus).PHP_EOL.
			"chargeEnabled: ".($this->chargeEnabled ? "yes" : "stop charge").PHP_EOL.
			"dischargeEnabled: ".($this->dischargeEnabled ? "yes" : "stop discharge").PHP_EOL.
			"chargeImmediately1: ".($this->chargeImmediately1 ? "yes" : "no").PHP_EOL.
			"chargeImmediately2: ".($this->chargeImmediately2 ? "yes" : "no").PHP_EOL.
			"fullChargeRequest: ".($this->fullChargeRequest ? "yes" : "no").PHP_EOL;
	}
}