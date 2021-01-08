<?php
const DEVICE = "/dev/cu.usbserial-0001";
$stream = fopen("/dev/cu.usbserial-0001", "r+"); //b?
var_dump($stream);
echo("opened\n");
exec("stty -f ".DEVICE. " 115200 cs8 -cstopb -parenb");
$cmd = "~20024642E00202FD33\r";
echo("sending...\n");
fwrite($stream, $cmd, strlen($cmd));
echo("rec\n");
stream_set_blocking($stream, false);
for($i = 0; $i < 50; ++$i){
	usleep(5000);
	var_dump(stream_get_contents($stream));
}
echo("recd\n");
fclose($stream);