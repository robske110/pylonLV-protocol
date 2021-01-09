<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class AnalogValue extends Command{
	public function __construct(?int $addr = null){
		parent::__construct(CID2::ANALOG_VALUE, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		Logger::debug("DataAI:".$data->getHex()); //#packs?
		$numCells = $data->getDec();
		Logger::debug("#Cells: ".$numCells);
		for($i = 0; $i < $numCells; ++$i){
			Logger::debug("Cell".($i+1)." Voltage: ".$data->getDec(2));
		}
		$numTemps = $data->getDec();
		Logger::debug("#Temps: ".$numTemps);
		for($i = 0; $i < $numTemps; ++$i){
			Logger::debug("Temp".($i+1).": ".($data->getDec(2)-2731));
		}
		$packCurrent = $data->getDec(2);
		Logger::debug("PackCurrent (A): ".$packCurrent*0.1);
		Logger::debug("PackVoltage (mV): ".$data->getDec(2));
		Logger::debug("PackResidual_old (mAh): ".$data->getDec(2));
		Logger::debug("CustomQuantity:".$data->getHex());
		Logger::debug("PackTotal_old (mAh): ".$data->getDec(2));
		Logger::debug("BattCycles: ".$data->getDec(2));
		$pR = $data->getDec(3);
		Logger::debug("PackResidual (mAh): ".$pR);
		$pT = $data->getDec(3);
		Logger::debug("PackTotal (mAh): ".$pT);
		Logger::debug("CALC_SOC:".(($pR/$pT)*100)."%");
	}
}