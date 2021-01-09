<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;

use robske_110\pylonlv\protocol\CRC;
use robske_110\pylonlv\protocol\exception\CommandError;
use robske_110\pylonlv\protocol\exception\CommandInvalid;

function hexStr(int $data, $len = 2){
	return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
}

function out(string $str){
	echo($str."\n");
}


class Frame{
	/**
	 * Frame:
	 * Byte len: |  1  |  1  |  1  |  1   |  1   |   2    | LEN/2 |   2    |  1  | (1 Byte sent as 2 Bytes (HEX-ASCII))
	 * ----------|-----|-----|-----|------|------|--------|-------|--------|-----|
	 * Content:  | SOI | VER | ADR | CID1 | CID2 | LENGTH | INFO  | CHKSUM | EOI |
	 */
	const SOI = 0x7E; //START OF INFORMATION
	const EOI = 0x0D; //END OF INFORMATION
	const PROTOCOL_VERSION = 0x20; //Protocol version in hexadecimal representation (read as 2.0)
	const CID1_BATTERY_DATA = 0x46; //battery data, only known CID1
	
	private int $version = self::PROTOCOL_VERSION; //VER
	private int $addr = 0x02; //ADR: Address 0-255
	private int $cid1 = self::CID1_BATTERY_DATA; //CID1: Control Identify Code 1
	private int $cid2 = 0x42; //CID2
	
	private FrameInfo $info; //LENGTH(with checksum)+INFO
	
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
		return chr( self::SOI).$payload.CRC::pylonCRC16($payload).chr(self::EOI);
	}
	
	public function decode(HexDataStream $data){
		if(ord($data->readRaw()) != self::SOI){
			echo("Could not find SOI\n");
		}
		out("ProtV:".$data->getHex());
		out("Address:".$data->getHex());
		out("CID1:".$data->getHex());
		$cid2 = $data->getHex();
		$cid2d = hexdec($cid2);
		if($cid2d != CID2response::NORMAL){
			switch($cid2d){
				case CID2response::VER_ERROR:
				case CID2response::CHKSUM_ERROR:
				case CID2response::LCHKSUM_ERROR:
				case CID2response::COMMAND_FORMAT_ERROR:
				case CID2response::ADR_ERROR:
				case CID2response::INTERNAL_COMMUNICATION_ERROR:
					throw new CommandError("Command returned an error: ".CID2response::toString($cid2d));
				case CID2response::CID2_INVALID:
				case CID2response::INFO_DATA_INVALID:
					throw new CommandInvalid("Command is invalid: ".CID2response::toString($cid2d));
			}
		}
		out("CID2:".$cid2);
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