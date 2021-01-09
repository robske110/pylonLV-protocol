<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexHelper;


class TurnOff extends Command{
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::TURNOFF, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	public function infoString(): string{
		return parent::infoString().PHP_EOL."TurnOff".PHP_EOL;
	}
}