<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;

abstract class CID2response{
	const NORMAL = 0x00;
	const VER_ERROR = 0x01;
	const CHKSUM_ERROR = 0x02;
	const LCHKSUM_ERROR = 0x03;
	const CID2_INVALID = 0x04;
	const COMMAND_FORMAT_ERROR = 0x05;
	const INFO_DATA_INVALID = 0x06;
	const ADR_ERROR = 0x90;
	const INTERNAL_COMMUNICATION_ERROR = 0x91;
	
	public static function toString(int $cid2): string{
		switch($cid2){
			case self::NORMAL:
				return "Normal (NORMAL)";
			case self::VER_ERROR:
				return "Version error (VER_ERROR)";
			case self::CHKSUM_ERROR:
				return "Checksum error (CHKSUM_ERROR)";
			case self::LCHKSUM_ERROR:
				return "Length checksum error (LCHKSUM_ERROR)";
			case self::CID2_INVALID:
				return "CID2 invalid (CID2_INVALID)";
			case self::COMMAND_FORMAT_ERROR:
				return "Command format error (COMMAND_FORMAT_ERROR)";
			case self::INFO_DATA_INVALID:
				return "Info data invalid (INFO_DATA_INVALID)";
			case self::ADR_ERROR:
				return "Address error (ADR_ERROR)";
			case self::INTERNAL_COMMUNICATION_ERROR:
				return "Internal communication error (INTERNAL_COMMUNICATION_ERROR)";
		}
	}
}