<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol;


class HexHelper{
	static function decToHexStr(int $data, $len = 2){
		return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
	}
}