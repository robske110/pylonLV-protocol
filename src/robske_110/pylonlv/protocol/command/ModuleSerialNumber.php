<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class ModuleSerialNumber extends Command{
	public string $moduleSerialNumber;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::SERIAL_NUMBER, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		$this->moduleSerialNumber = hex2bin($data->getHex(16));
	}
	
	public function infoString(): string{
		return  parent::infoString().PHP_EOL.
			"ManufacturerInfo:".PHP_EOL.
			"Module Serial Number: ".$this->moduleSerialNumber.PHP_EOL;
	}
}