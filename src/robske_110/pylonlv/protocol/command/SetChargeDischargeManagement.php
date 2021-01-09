<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexHelper as Hex;

class SetChargeDischargeManagement extends Command{
	public int $chargeVoltageLimit; //mV
	public int $dischargeVoltageLimit; //mV
	public int $chargeCurrentLimit; //mA
	public int $dischargeCurrentLimit; //negative, in mA
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::SET_CHG_DISCHG_MANAGEMENT, $addr);
	}
	
	protected function encodeInfo(): string{
		$info = Hex::decToHexStr($this->addr);
		$info .= Hex::decToHexStr($this->chargeVoltageLimit, 4);
		$info .= Hex::decToHexStr($this->dischargeVoltageLimit, 4);
		$info .= Hex::decToHexStr((int) ($this->chargeCurrentLimit/100), 4);
		$info .= Hex::decToHexStr((int) ($this->dischargeCurrentLimit/100), 4);
		return $info;
	}
	
	public function infoString(): string{
		return parent::infoString().PHP_EOL."SetChargeDischargeManagement".PHP_EOL;
	}
}