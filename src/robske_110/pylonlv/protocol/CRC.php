<?php
namespace robske_110\pylonlv\protocol;

class CRC{
	//crc for pylon: sum of ordinal values, invert, add 1
	public static function crc16_pylon(string $data){
		$sum = 0;
		for ($i = 0; $i < strlen($data); $i++){
			$sum += ord($data[$i]);
		}
		#$sum = $sum % 2**16;
		return strtoupper(dechex(((~$sum) + 1) & 0xFFFF));
	}
}