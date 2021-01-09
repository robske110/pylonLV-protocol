<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;


class HexHelper{
	static function decToHexStr(int $data, $len = 2){
		return strtoupper(str_pad(substr(dechex($data), -$len), $len, "0", STR_PAD_LEFT));
	}
	
	static function signedHexToDec(string $hex): int{
		$dec = hexdec($hex);
		if($dec >> strlen($hex)*4-1){
			return -((~hexdec($hex) & 0xFFFF) + 1);
		}else{
			return $dec;
		}
	}
}