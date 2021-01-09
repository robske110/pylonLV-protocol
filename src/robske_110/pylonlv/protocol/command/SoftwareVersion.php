<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper as Hex;

class SoftwareVersion extends Command{
	public int $manufactureVersion;
	public int $mainlineVersion;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::SOFTWARE_VERSION, $addr);
	}
	
	protected function encodeInfo(): string{
		return Hex::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		$this->manufactureVersion = $data->getDec(2);
		$this->mainlineVersion = $data->getDec(3);
	}
	
	public function infoString(): string{
		return parent::infoString().PHP_EOL.
			"SoftwareVersion:".PHP_EOL.
			"Manufacture version: ".Hex::decToHexStr($this->manufactureVersion).PHP_EOL.
			"Main line version: ".Hex::decToHexStr($this->mainlineVersion).PHP_EOL;
	}
}