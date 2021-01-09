<?php
namespace robske_110\pylonlv;

use robske_110\Logger\Logger;

class PylonSerial{
	/** @var string */
	private $deviceFile;
	
	private $stream;
	
	public function __construct(string $deviceFile){
		$this->deviceFile = $deviceFile;
	}
	
	public function open(){
		$this->stream = fopen($this->deviceFile, "r+"); //b?
		if($this->stream === false){
			echo("FAILED TO OPEN");
		}
		exec("stty -f ".$this->deviceFile." 115200 cs8 -cstopb -parenb");
		stream_set_blocking($this->stream, false);
	}
	
	public function send(string $str){
		Logger::debug("Sending ".$str);
		if(fwrite($this->stream, $str, strlen($str)) !== strlen($str)){
			echo("FAILED TO WRITE");
		}
	}

	
	public function readUntil(int $maxReads = 500){
		$str = "";
		for($i = 0; $i < $maxReads; ++$i){
			usleep(5000);
			$str .= stream_get_contents($this->stream);
			if(str_contains($str, "\r")){
				break;
			}
		}
		return $str;
	}
	
	public function close(){
		fclose($this->stream);
	}
	
	public function __destruct(){
		$this->close();
	}
}

/*$pylonSerial = new PylonSerial("/dev/cu.SLAB_USBtoUART");
$pylonSerial->open();
$start = microtime(true);
$pylonSerial->send("~20024642E00202FD33\r");
var_dump($pylonSerial->readUntil());
echo("Took ".(microtime(true)-$start)."s");*/