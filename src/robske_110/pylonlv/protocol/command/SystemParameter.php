<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\protocol\HexHelper;

class SystemParameter extends Command{
	public int $cellHighVoltageLimit;
	public int $cellLowVoltageLimit;   //alarm
	public int $cellUnderVoltageLimit; //protect
	public int $chargeHighTemperatureLimit; //celsius
	public int $chargeLowTemperatureLimit;
	public int $chargeCurrentLimit;
	public int $moduleHighVoltageLimit;
	public int $moduleLowVoltageLimit; //alarm
	public int $moduleUnderVoltageLimit; //protect
	public int $dischargeHighTemperatureLimit;
	public int $dischargeLowTemperatureLimit;
	public int $dischargeCurrentLimit;
	
	public function __construct(?int $addr = null){
		parent::__construct(CID2::SYSTEM_PARAM, $addr);
	}
	
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		$this->cellHighVoltageLimit = $data->getDec(2);
		$this->cellLowVoltageLimit = $data->getDec(2);
		$this->cellUnderVoltageLimit = $data->getDec(2);
		$this->chargeHighTemperatureLimit = (int) ($data->getDec(2) - 2731) / 10;
		$this->chargeLowTemperatureLimit = (int) ($data->getDec(2) - 2731) / 10;
		#$this->chargeCurrentLimit = $data->getDec(2);
		$this->chargeCurrentLimit = HexHelper::signedHexToDec($data->getHex(2))*100;
		$this->moduleHighVoltageLimit = $data->getDec(2);
		$this->moduleLowVoltageLimit = $data->getDec(2);
		$this->moduleUnderVoltageLimit = $data->getDec(2);
		$this->dischargeHighTemperatureLimit = (int) ($data->getDec(2) - 2731) / 10;
		$this->dischargeLowTemperatureLimit = (int) ($data->getDec(2) - 2731) / 10;
		$this->dischargeCurrentLimit = HexHelper::signedHexToDec($data->getHex(2))*100;
	}
	
	public function infoString(): string{
		return parent::infoString().PHP_EOL.
			"SystemParameter:".PHP_EOL.
			"CellHighVoltageLimit: ".$this->cellHighVoltageLimit.PHP_EOL.
			"CellLowVoltageLimit: ".$this->cellLowVoltageLimit.PHP_EOL.
			"CellUnderVoltageLimit: ".$this->cellUnderVoltageLimit.PHP_EOL.
			"ChargeHighTemperatureLimit: ".$this->chargeHighTemperatureLimit.PHP_EOL.
			"ChargeLowTemperatureLimit: ".$this->chargeLowTemperatureLimit.PHP_EOL.
			"ChargeCurrentLimit: ".($this->chargeCurrentLimit/1000).PHP_EOL.
			"ModuleHighVoltageLimit: ".$this->moduleHighVoltageLimit.PHP_EOL.
			"ModuleLowVoltageLimit: ".$this->moduleLowVoltageLimit.PHP_EOL.
			"ModuleUnderVoltageLimit: ".$this->moduleUnderVoltageLimit.PHP_EOL.
			"DischargeHighTemperatureLimit: ".$this->dischargeHighTemperatureLimit.PHP_EOL.
			"DischargeLowTemperatureLimit: ".$this->dischargeLowTemperatureLimit.PHP_EOL.
			"DischargeCurrentLimit: ".($this->dischargeCurrentLimit/1000).PHP_EOL;
	}
}