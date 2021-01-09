<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\CRC;
use robske_110\pylonlv\protocol\exception\CommandError;
use robske_110\pylonlv\protocol\exception\CommandInvalid;
use robske_110\pylonlv\protocol\exception\FrameDecodeError;

function hexStr(int $data, $len = 2){
	return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
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
		Logger::debug("Payload: ".$payload."\n");
		return chr( self::SOI).$payload.CRC::pylonCRC16($payload).chr(self::EOI);
	}
	
	public function decode(HexDataStream $data){
		if(ord($data->readRaw()) != self::SOI){
			throw new FrameDecodeError("Could not find SOI");
		}
		Logger::debug("ProtV:".$data->getHex());
		Logger::debug("Address:".$data->getHex());
		Logger::debug("CID1:".$data->getHex());
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
		Logger::debug("CID2:".$cid2);
		$val = $data->getDec(2);
		$byteLen = ($val & 0x0FFF)/2; //strip crc from length
		Logger::debug("byteLen: ".$byteLen);
		var_dump(decbin($val));
		var_dump(decbin(CRC::pylonInfoLenCRC($byteLen)));
		if($val !== CRC::pylonInfoLenCRC($byteLen)){
			Logger::debug("ERROR: CRC MISMATCH!!!!");
		}
		if($byteLen >= 1){
			$readChars = $data->remaining();
			Logger::debug("InfoFlag:".decbin($data->getDec()));
			
			$this->decodeInfo($data, $byteLen-1);
			
			$readChars = $readChars-$data->remaining();
			Logger::debug("read ".$readChars." chars");
			if($readChars < $byteLen*2){
				$skipChars = $byteLen*2 - $readChars;
				Logger::warning("Did not decode whole INFO: Skipping ".$skipChars." chars");
				$data->skipPos($skipChars);
			}elseif($readChars > $byteLen*2){
				throw new FrameDecodeError("Error while decoding INFO: Read past INFO block.");
			}
		}
		
		var_dump($data->remaining());
		
		$payload = substr($data->rawData(), 1, $data->rawPos()-1);
		if(CRC::pylonCRC16($payload) !== $data->getHex(2)){
			Logger::debug("ERROR: CRC MISMATCH!!!!!");
		}else{
			Logger::debug("CRC OK");
		}
		if(ord($data->readRaw()) != self::EOI){
			throw new FrameDecodeError("Could not find EOI");
		}
		var_dump($data->remaining());
	}
	
	private function decodeInfo(HexDataStream $data, int $infoLength){
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

class FrameInfo{
	
	public function encode(): string{
		//retarded crc for pylon length (WHY A CRC IN A FRAME THAT ALREADY HAS A CRC)
		$len = 2;
		$lengthWithCRC = CRC::pylonInfoLenCRC($len);
		var_dump(decbin($lengthWithCRC));
		return hexStr($lengthWithCRC, 4)."02";
	}
}