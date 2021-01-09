<?php
namespace robske_110\pylonlv\protocol;
require("../../../Autoloader.php");

use robske_110\pylonlv\protocol\CRC;

function hexStr(string $data, $len = 2){
	return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
}

$b = new Frame();
/*$b->decode("~200246040000FDAE
");*/
$b->decode(new HexDataStream("~20024600F07A11020F0CDE0CDC0CE20CDE0CE30CE30CE30CDF0CD80CDD0CE00CDE0CE20CE10CDF050B870B870B870B870B870000C117FFFF04FFFF00000070BC012110E1E7\r"));
/*$b->decode("~20014600C06E11010F0D450D440D450D440D450D440D3E0D450D4A0D4A0D4B0D4A0D4A0D4A0D4A050BC30BC30BC30BCD0BCD0000C725BF6802C3500002E553
");*/
echo("\n\n\n");

function out(string $str){
	echo($str."\n");
}

function readByte($data, &$pos){
	$pos += 2;
	return $data[$pos].$data[$pos+1];
}

class CID2{
	const ANALOG_VALUE = 0x42;
	const ALARM_INFO = 0x44;
	const SYSTEM_PARAM = 0x47;
	const PROTOCOL_VERSION = 0x4F;
	const MANUFACTURER = 0x51;
	const CHARGE_DISCHG_MANAGMENT = 0x92;
	const SERIAL_NUMBER = 0x93;
	const SET_CHG_DISCHG_MANAGMENT = 0x94;
	const TURNOFF = 0x95;
	const FIRMWARE_INFO = 0x96;
}

class CID2response{
	const NORMAL = 0x00;
	const VER_ERROR = 0x01;
	const CHKSUM_ERROR = 0x02;
	const LCHKSUM_ERROR = 0x03;
	const CID2_INVALID = 0x04;
	const COMMAND_FORMAT_ERROR = 0x05;
	const INFO_DATA_INVALID = 0x06;
	const ADR_ERROR = 0x90;
	const INTERNAL_COMMUNICATION_ERROR = 0x91;
}

class Frame{
	/**
	 * Frame:
	 * Byte len: |  1  |  1  |  1  |  1   |  1   |   2    | LEN/2 |   2    |  1  |
	 * ----------|-----|-----|-----|------|------|--------|-------|--------|-----|
	 * Content:  | SOI | VER | ADR | CID1 | CID2 | LENGTH | INFO  | CHKSUM | EOI |
	 */
	const SOI = 0x7E; //START OF INFORMATION
	const EOI = 0x0D; //END OF INFORMATION
	const PROTOCOL_VERSION = 0x20; //Protocol version in hexadecimal representation (read as 2.0)
	const CID1_BATTERY_DATA = 0x46; //battery data CID1
	
	private int $version = self::PROTOCOL_VERSION; // VER byte
	private int $addr = 0x02; //ADR byte: Address 0-255
	private int $cid1 = self::CID1_BATTERY_DATA; //CID1 byte: Control Identify Code
	private int $cid2 = 0x42;
	
	private FrameInfo $info;
	
	public function __construct(?FrameInfo $info = null){
		$this->info = $info ?? new FrameInfo();
	}
	
	public function encode(): string{
		$payload = hexStr($this->version);
		$payload .= hexStr($this->addr);
		$payload .= hexStr($this->cid1);
		$payload .= hexStr($this->cid2);
		$payload .= $this->info->encode();
		echo("Payload: ".$payload."\n");
		return pack("c", self::SOI).$payload.CRC::pylonCRC16($payload).pack("c", self::EOI);
	}
	
	public function decode(HexDataStream $data){
		if(ord($data->readRaw()) != self::SOI){
			echo("Could not find SOI\n");
		}
		out("ProtV:".$data->getHex());
		out("Address:".$data->getHex());
		out("CID1:".$data->getHex());
		out("CID2:".$data->getHex());
		$val = $data->getDec(2);
		$byteLen = ($val & 0x0FFF)/2; //strip crc from length
		out("byteLen: ".$byteLen);
		/*var_dump(decbin($val));
		var_dump(decbin(CRC::pylonInfoLenCRC($byteLen)));
		if($val !== CRC::pylonInfoLenCRC($byteLen)){
			echo("ERROR: CRC MISMATCH!!!!");
		}*/
		out("InfoFlag:".decbin($data->getDec()));
		out("DataAI:".$data->getHex()); //#packs?
		$numCells = $data->getDec();
		out("#Cells: ".$numCells);
		for($i = 0; $i < $numCells; ++$i){
			out("Cell".($i+1)." Voltage: ".$data->getDec(2));
		}
		$numTemps = $data->getDec();
		out("#Temps: ".$numTemps);
		for($i = 0; $i < $numTemps; ++$i){
			out("Temp".($i+1).": ".($data->getDec(2)-2731));
		}
		$packCurrent = $data->getDec(2);
		out("PackCurrent (A): ".$packCurrent*0.1);
		out("PackVoltage (mV): ".$data->getDec(2));
		out("PackResidual_old (mAh): ".$data->getDec(2));
		out("CustomQuantity:".$data->getHex());
		out("PackTotal_old (mAh): ".$data->getDec(2));
		out("BattCycles: ".$data->getDec(2));
		$pR = $data->getDec(3);
		out("PackResidual (mAh): ".$pR);
		$pT = $data->getDec(3);
		out("PackTotal (mAh): ".$pT);
		out("CALC_SOC:".(($pR/$pT)*100)."%");
		$payload = substr($data->rawData(), 1, $data->rawPos()-1);
		if(CRC::pylonCRC16($payload) !== $data->getHex(2)){
			echo("ERROR: CRC MISMATCH!!!!!");
		}else{
			echo("CRC OK");
		}
		if(ord($data->readRaw()) != self::EOI){
			echo("Could not find EOI\n");
		}
		var_dump($data->remaining());
	}
}

class FrameInfo{
	
	public function encode(): string{
		//retarded crc for pylon length (WHY A CRC IN A FRAME THAT ALREADY HAS A CRC)
		$len = 2;
		$lengthWithCRC = CRC::pylonInfoLenCRC($len);
		var_dump(decbin($lengthWithCRC));
		return hexStr($lengthWithCRC, 4)."02";
	}
}

class DataInfo{
	//AI Fixed-point
	//AF
	//FLAG
	//RUN_STATE
	//WARN_STATE
}

class CommandInfo{
	//Command Group 1
	//Command Type 1
	//Command ID 1
	//Command Time 7
}

$a = new Frame();
var_dump($a->encode());
var_dump(bin2hex($a->encode()));