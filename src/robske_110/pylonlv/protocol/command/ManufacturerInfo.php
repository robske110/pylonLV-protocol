<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper as Hex;

class ManufacturerInfo extends Command{
	public string $batteryName;
	public int $swVersion;
	public string $manufacturerName;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::MANUFACTURER, $addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		var_dump($infoLength);
		$this->batteryName = hex2bin($data->getHex(10));
		$this->swVersion = $data->getDec();
		$this->manufacturerName = hex2bin($data->getHex(20));
	}
	
	public function infoString(): string{
		return parent::infoString().PHP_EOL.
			"ManufacturerInfo:".PHP_EOL.
			"Battery name: ".$this->batteryName.PHP_EOL.
			"SW version: ".Hex::decToHexStr($this->swVersion).PHP_EOL.
			"Manufacturer name: ".$this->manufacturerName.PHP_EOL;
	}
}