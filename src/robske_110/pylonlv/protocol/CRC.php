<?php
namespace robske_110\pylonlv\protocol;

class CRC{
	//crc for pylon: sum of ordinal values, invert, add 1
	public static function pylonCRC16(string $data){
		$sum = 0;
		for ($i = 0; $i < strlen($data); $i++){
			$sum += ord($data[$i]);
		}
		#$sum = $sum % 2**16;
		return strtoupper(dechex(((~$sum) + 1) & 0xFFFF));
	}
	
	public static function pylonInfoLenCRC(int $len){
		$crc = ($len >> 8 & 0xF) + ($len >> 4 & 0xF) + ($len & 0xF); //12bit len number, add each 4 bit as a number together
		$crc = (~($crc % 16) + 1) & 0xF;
		return $len + ($crc << 12);
	}
}