<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;


abstract class CID2{
	const ANALOG_VALUE = 0x42;
	const ALARM_INFO = 0x44;
	const SYSTEM_PARAM = 0x47;
	const PROTOCOL_VERSION = 0x4F;
	const MANUFACTURER = 0x51;
	const CHARGE_DISCHG_MANAGEMENT = 0x92;
	const SERIAL_NUMBER = 0x93;
	const SET_CHG_DISCHG_MANAGEMENT = 0x94;
	const TURNOFF = 0x95;
	const SOFTWARE_VERSION = 0x96;
}