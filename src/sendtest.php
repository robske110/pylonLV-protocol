<?php
const DEVICE = "/dev/cu.SLAB_USBtoUART";
$stream = fopen(DEVICE, "r+"); //b?
var_dump($stream);
echo("opened\n");
exec("stty -f ".DEVICE. " 115200 cs8 -cstopb -parenb");
$cmd = "~20024642E00202FD33\r";
echo("sending...\n");
fwrite($stream, $cmd, strlen($cmd));
echo("rec\n");
stream_set_blocking($stream, false);
function readUntil($stream, int $maxReads = 500){
	$str = "";
	for($i = 0; $i < $maxReads; ++$i){
		usleep(5000);
		$str .= stream_get_contents($stream);
		if(str_contains($str, "\r")){
			break;
		}
	}
	return $str;
}
var_dump(readUntil($stream));

echo("recd\n");
fclose($stream);