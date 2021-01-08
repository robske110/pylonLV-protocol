<?php

$command = "1203400456ABCEFE";
$command = "20014682C0048520"; #FCC3
$command = "20014642E00201";
#		   "20024642E00202FD33"
var_dump(crc16_pylon($command));

//crc for pylon: sum of ordinal values, modulo 
function crc16_pylon(string $data){
	$sum = 0;
	for($i = 0; $i < strlen($data); $i++){
		$sum += ord($data[$i]);
	}
	#var_dump($sum);
	#var_dump(dechex($sum));
	#$sum = $sum % 2**16;
	return strtoupper(dechex(((~$sum)+1) & 0xFFFF));
}

/*

var_dump(hexdec("038E"));
var_dump(2**16);
var_dump(13828758 % (2**16));
var_dump(13828758 >> 16);

*/
	   #323030313436343245303032303146443335
$cmd = "323030323436343245303032303246443333";
for($i = 0; $i < strlen($cmd); $i += 2){
	echo(chr(hexdec(substr($cmd, $i, 2))));
}
echo("\n");

#var_dump(dechex(bindec("1110")));
#var_dump(decbin(hexdec("E002")));