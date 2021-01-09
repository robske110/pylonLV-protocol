<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class AlarmInfo extends Command{
	public int $cellCount;
	/** @var int[] */
	public array $cellVoltages;
	public int $temperatureCount;
	/** @var int[] */
	public array $temperatures;
	const TEMP_SENSOR_INFO = [
		"BMS", "Cell 1-4", "Cell 5-8", "Cell 9-12", "Cell 13-15/16", "Mosfet"
	];
	public int $chargeCurrent;
	public int $moduleVoltage;
	public int $dischargeCurrent;
	
	public int $status1;
	public bool $moduleUnderVoltage;
	public bool $chargeOverTemperature;
	public bool $dischargeOverTemperature;
	public bool $dischargeOverCurrent;
	public bool $chargeOverCurrent;
	public bool $cellUnderVoltage;
	public bool $moduleOverVoltage;
	
	public int $status2;
	public bool $usingBatteryModulePower;
	public bool $dischargeMosfet;
	public bool $chargeMosfet;
	public bool $preMosfet; //reserve, currently not used
	
	public int $status3;
	public bool $effectiveChargeCurrent; //charge current over 0.1A
	public bool $effectiveDischargeCurrent; //discharge current over 0.1A
	public bool $heater; //reserve, currently not used
	public bool $fullyCharged;
	public bool $buzzer;
	
	public int $status4;
	public int $status5;
	/** @var bool[] */
	public array $cellVoltageAbnormal;
	
	const STATE_NORMAL = 0x00;
	const STATE_LOWER_LIMIT_BREACH = 0x01;
	const STATE_HIGHER_LIMIT_BREACH = 0x02;
	const STATE_OTHER_ERROR = 0xF0;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::ALARM_INFO, $addr);
	}
	
	protected function encodeInfo(): string{
		return HexHelper::decToHexStr($this->addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		Logger::debug("cmdval:".$data->getHex()); //#packs?
		$this->cellCount = $data->getDec();
		for($i = 0; $i < $this->cellCount; ++$i){
			$this->cellVoltages[$i] = $data->getDec();
		}
		$this->temperatureCount = $data->getDec();
		for($i = 0; $i < $this->temperatureCount; ++$i){
			$this->temperatures[$i] = $data->getDec();
		}
		
		$this->chargeCurrent = $data->getDec();
		$this->moduleVoltage = $data->getDec();
		$this->dischargeCurrent = $data->getDec();
		
		$this->status1 = $data->getDec();
		$this->moduleUnderVoltage = (bool) ($this->status1 & 0b10000000);
		$this->chargeOverTemperature = (bool) ($this->status1 & 0b01000000);
		$this->dischargeOverTemperature = (bool) ($this->status1 & 0b00100000);
		$this->dischargeOverCurrent = (bool) ($this->status1 & 0b00010000);
		$this->chargeOverCurrent = (bool) ($this->status1 & 0b00000100);
		$this->cellUnderVoltage = (bool) ($this->status1 & 0b00000010);
		$this->moduleOverVoltage = (bool) ($this->status1 & 0b00000001);
		
		$this->status2 = $data->getDec();
		$this->usingBatteryModulePower = (bool) ($this->status1 & 0b00001000);
		$this->dischargeMosfet = (bool) ($this->status1 & 0b00000100);
		$this->chargeMosfet = (bool) ($this->status1 & 0b00000010);
		$this->preMosfet = (bool) ($this->status1 & 0b00000001);
		
		$this->status3 = $data->getDec();
		$this->effectiveChargeCurrent = (bool) ($this->status1 & 0b10000000);
		$this->effectiveDischargeCurrent = (bool) ($this->status1 & 0b01000000);
		$this->heater = (bool) ($this->status1 & 0b00100000);
		$this->fullyCharged = (bool) ($this->status1 & 0b00001000);
		$this->buzzer = (bool) ($this->status1 & 0b00000001);
		
		$this->status4 = $data->getDec();
		var_dump(str_pad(decbin($this->status4), 8, "0", STR_PAD_LEFT));
		for($i = 0; $i < 8; ++$i){
			$this->cellVoltageAbnormal[$i] = (bool) str_pad(decbin($this->status4), 8, "0", STR_PAD_LEFT)[$i];
		}
		$this->status5 = $data->getDec();
		for($i = 0; $i < 8; ++$i){
			$this->cellVoltageAbnormal[$i+8] = (bool) str_pad(decbin($this->status5), 8,"0", STR_PAD_LEFT)[$i];
		}
	}
	
	public static function alarmStateStr(int $alarmState): string{
		switch($alarmState){
			case self::STATE_NORMAL:
				return "Normal";
			case self::STATE_LOWER_LIMIT_BREACH:
				return "Below lower limit.";
			case self::STATE_HIGHER_LIMIT_BREACH:
				return "Above higher limit.";
			case self::STATE_OTHER_ERROR:
				return "other error";
			default:
				return "Unknown alarm state";
		}
	}
	
	public function infoString(): string{
		$str = parent::infoString().PHP_EOL;
		$str .= "Cell count: ".$this->cellCount.PHP_EOL;
		foreach($this->cellVoltages as $i => $cellState){
			$str .= "Cell #".($i+1).": ".self::alarmStateStr($cellState).PHP_EOL;
		}
		$str .= "Temperature count: ".$this->temperatureCount.PHP_EOL;
		foreach($this->temperatures as $i => $tempState){
			$str .= "Temperature #".$i." (".(self::TEMP_SENSOR_INFO[$i] ?? "unknown")."): ".self::alarmStateStr($tempState).PHP_EOL;
		}
		$str .= "Charge current: ".self::alarmStateStr($this->chargeCurrent).PHP_EOL;
		$str .= "Module voltage: ".self::alarmStateStr($this->chargeCurrent).PHP_EOL;
		$str .= "Discharge current: ".self::alarmStateStr($this->chargeCurrent).PHP_EOL;
		
		
		$str .= "moduleUnderVoltage: ".($this->moduleUnderVoltage ? "triggered" : "normal").PHP_EOL;
		$str .= "chargeOverTemperature: ".($this->chargeOverTemperature ? "triggered" : "normal").PHP_EOL;
		$str .= "dischargeOverTemperature: ".($this->dischargeOverTemperature ? "triggered" : "normal").PHP_EOL;
		$str .= "dischargeOverCurrent: ".($this->dischargeOverCurrent ? "triggered" : "normal").PHP_EOL;
		$str .= "chargeOverCurrent: ".($this->chargeOverCurrent ? "triggered" : "normal").PHP_EOL;
		$str .= "cellUnderVoltage: ".($this->cellUnderVoltage ? "triggered" : "normal").PHP_EOL;
		$str .= "moduleOverVoltage: ".($this->moduleOverVoltage ? "triggered" : "normal").PHP_EOL;
		
		$str .= "usingBatteryModulePower: ".($this->usingBatteryModulePower ? "yes" : "no").PHP_EOL;
		$str .= "dischargeMosfet: ".($this->dischargeMosfet ? "on" : "off").PHP_EOL;
		$str .= "chargeMosfet: ".($this->chargeMosfet ? "on" : "off").PHP_EOL;
		$str .= "preMosfet: ".($this->preMosfet ? "on" : "off").PHP_EOL;
		
		$str .= "effectiveChargeCurrent: ".($this->effectiveChargeCurrent ? "yes" : "no").PHP_EOL;
		$str .= "effectiveDischargeCurrent: ".($this->effectiveDischargeCurrent ? "yes" : "no").PHP_EOL;
		$str .= "heater: ".($this->heater ? "on" : "off").PHP_EOL;
		$str .= "fullyCharged: ".($this->fullyCharged ? "yes" : "no").PHP_EOL;
		$str .= "buzzer: ".($this->buzzer ? "on" : "off").PHP_EOL;
		
		
		$str .= "Cells abnormal: ";
		foreach($this->cellVoltageAbnormal as $i => $cellState){
			$str .= "#".($i+1).": ".($cellState ? "ABNORMAL": "OK")." ";
		}
		return $str.PHP_EOL;
	}
}