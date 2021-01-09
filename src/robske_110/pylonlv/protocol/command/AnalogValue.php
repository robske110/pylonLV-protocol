<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class AnalogValue extends Command{
	public int $cellCount;
	/** @var int[] */
	public array $cellVoltages;
	public int $temperatureCount;
	/** @var int[] */
	public array $temperatures;
	const TEMP_SENSOR_INFO = [
		"BMS", "Cell 1-4", "Cell 5-8", "Cell 9-12", "Cell 13-15/16", "Mosfet"
	];
	public int $moduleCurrent;
	public int $moduleVoltage;
	public int $moduleRemainCapacity;
	public int $moduleTotalCapacity;
	public int $moduleCycles;
	
	public int $userDefined;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::ANALOG_VALUE, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		Logger::debug("cmdval:".$data->getHex()); //#packs?
		$this->cellCount= $data->getDec();
		for($i = 0; $i < $this->cellCount; ++$i){
			$this->cellVoltages[$i] = $data->getDec(2);
		}
		$this->temperatureCount = $data->getDec();
		for($i = 0; $i < $this->temperatureCount; ++$i){
			$this->temperatures[$i] = (int) (($data->getDec(2)-2731)/10);
		}
		$this->moduleCurrent = HexHelper::signedHexToDec($data->getHex(2))*100;
		$this->moduleVoltage = $data->getDec(2);
		
		$this->moduleRemainCapacity = $data->getDec(2);
		$this->userDefined = $data->getDec();
		$this->moduleTotalCapacity = $data->getDec(2);
		$this->moduleCycles = $data->getDec(2);
		if($this->userDefined === 4){
			$this->moduleRemainCapacity = $data->getDec(3);
			$this->moduleTotalCapacity = $data->getDec(3);
		}
	}
	
	public function infoString(): string{
		$str = parent::infoString().PHP_EOL;
		$str .= "Cell count: ".$this->cellCount.PHP_EOL;
		foreach($this->cellVoltages as $i => $cellVoltage){
			$str .= "Cell #".$i.": ".$cellVoltage."mV".PHP_EOL;
		}
		$str .= "Temperature count: ".$this->temperatureCount.PHP_EOL;
		foreach($this->temperatures as $i => $temperature){
			$str .= "Temperature #".$i." (".(self::TEMP_SENSOR_INFO[$i] ?? "unknown")."): ".$temperature."°C".PHP_EOL;
		}
		$str .= "Module Current (A): ".($this->moduleCurrent/1000).PHP_EOL;
		$str .= "Module Voltage (mV): ".$this->moduleVoltage.PHP_EOL;
		$str .= "Module Remain Capacity (mAh): ".$this->moduleRemainCapacity.PHP_EOL;
		$str .= "Module Total Capacity (mAh): ".$this->moduleTotalCapacity.PHP_EOL;
		$str .= "(calculated) SOC: ".(($this->moduleRemainCapacity/$this->moduleTotalCapacity)*100).PHP_EOL;
		$str .= "Module Cycles: ".$this->moduleCycles.PHP_EOL;
		return $str;
	}
}