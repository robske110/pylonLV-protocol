<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\exception\CommandError;
use robske_110\pylonlv\protocol\exception\CommandInvalid;
use robske_110\pylonlv\protocol\exception\FrameDecodeError;
use robske_110\pylonlv\protocol\HexHelper as Hex;

class Frame{
	/**
	 * Frame:
	 * Byte len: |  1  |  1  |  1  |  1   |  1   |   2    | LEN/2 |   2    |  1  | (1 Byte sent as 2 Bytes (HEX-ASCII))
	 * ----------|-----|-----|-----|------|------|--------|-------|--------|-----|
	 * Content:  | SOI | VER | ADR | CID1 | CID2 | LENGTH | INFO  | CHKSUM | EOI |
	 */
	const SOI = 0x7E; //SOI: START OF INFORMATION
	const EOI = 0x0D; //EOI: END OF INFORMATION
	const PROTOCOL_VERSION = 0x20; //Protocol version in hexadecimal representation (read as 2.0)
	const CID1_BATTERY_DATA = 0x46; //battery data, only known CID1
	
	protected int $version = self::PROTOCOL_VERSION; //VER
	protected ?int $addr; //ADR: Address 0-255
	protected int $cid1 = self::CID1_BATTERY_DATA; //CID1: Control Identify Code 1
	protected int $cid2; //CID2
	
	/**
	 * Frame constructor.
	 * @param int $cid2      1 byte commandID. See CID2 for possible values.
	 * @param int|null $addr 1 byte address. This can be null if this frame is only used for decoding.
	 */
	public function __construct(int $cid2, ?int $addr = null){
		$this->cid2 = $cid2;
		$this->addr = $addr;
	}
	
	public function encode(): string{
		$payload = Hex::decToHexStr($this->version);
		if($this->addr === null){
			throw new \Exception("Can not encode a Frame with no address!");
		}
		$payload .= Hex::decToHexStr($this->addr);
		$payload .= Hex::decToHexStr($this->cid1);
		$payload .= Hex::decToHexStr($this->cid2);
		$info = $this->encodeInfo(); //INFO
		$lengthWithCRC = CRC::pylonInfoLenCRC(strlen($info));
		var_dump(decbin($lengthWithCRC));
		$payload .= Hex::decToHexStr($lengthWithCRC, 4).$info; //LENGTH (with checksum) + INFO
		Logger::debug("Payload: ".$payload."\n");
		return chr( self::SOI).$payload.CRC::pylonCRC16($payload).chr(self::EOI);
	}
	
	protected function encodeInfo(): string{
		return "";
	}
	
	public function decode(HexDataStream $data){
		if(ord($data->readRaw()) != self::SOI){
			throw new FrameDecodeError("Could not find SOI");
		}
		Logger::debug("ProtV:".$data->getHex());
		$addr = $data->getDec();
		Logger::debug("Address:".$addr);
		if($this->addr !== null && $this->addr !== $addr){
			Logger::warning("Error while decoding FrameHeader: Address does not mach!");
		}
		$this->addr = $addr;
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
		
		$lenRaw = $data->getDec(2);
		$byteLen = ($lenRaw & 0x0FFF)/2; //strip crc from length and divide by two to get byte length
		Logger::debug("byteLen: ".$byteLen);
		var_dump(decbin($lenRaw));
		var_dump(decbin(CRC::pylonInfoLenCRC($byteLen*2)));
		if($lenRaw !== CRC::pylonInfoLenCRC($byteLen*2)){
			Logger::warning("Length checksum not correct!");
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
			Logger::warning("Frame checksum not correct!");
		}
		
		if(ord($data->readRaw()) != self::EOI){
			throw new FrameDecodeError("Could not find EOI");
		}
	}
	
	/**
	 * When implementing an info decoder, override this function and do not call it!
	 * @param HexDataStream $data
	 * @param int $infoLength
	 */
	protected function decodeInfo(HexDataStream $data, int $infoLength){
		Logger::log("Skipped ".$infoLength." unknown bytes in INFO.");
		$data->skipPos($infoLength*2);
	}
	
	public function infoString(): string{
		//TODO
	}
	
	public function __toString(): string{
		return $this->infoString();
	}
}