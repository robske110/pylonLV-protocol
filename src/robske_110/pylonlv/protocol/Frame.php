<?php
namespace robske_110\pylonlv\protocol;
require("../../../Autoloader.php");

use robske_110\pylonlv\protocol\CRC;

function hexStr(string $data, $len = 2){
	return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
}

$b = new Frame();
/*$b->decode("~200246040000FDAE
");*/
$b->decode(new HexDataStream("~20024600F07A11020F0CDE0CDC0CE20CDE0CE30CE30CE30CDF0CD80CDD0CE00CDE0CE20CE10CDF050B870B870B870B870B870000C117FFFF04FFFF00000070BC012110E1E7\r"));
/*$b->decode("~20014600C06E11010F0D450D440D450D440D450D440D3E0D450D4A0D4A0D4B0D4A0D4A0D4A0D4A050BC30BC30BC30BCD0BCD0000C725BF6802C3500002E553
");*/
echo("\n\n\n");

function out(string $str){
	echo($str."\n");
}

function readByte($data, &$pos){
	$pos += 2;
	return $data[$pos].$data[$pos+1];
}

class Frame{
	const SOI = 0x7E; //START OF INFORMATION
	const EOI = 0x0D; //END OF INFORMATION
	
	private int $version = 0x20; // VER byte: Hexadecimal representation
	private int $addr = 0x02;
	private int $cid1 = 0x46;
	private int $cid2 = 0x42;
	
	private FrameInfo $info;
	
	public function __construct(?FrameInfo $info = null){
		$this->info = $info ?? new FrameInfo();
	}
	
	public function encode(): string{
		$payload = hexStr($this->version);
		$payload .= hexStr($this->addr);
		$payload .= hexStr($this->cid1);
		$payload .= hexStr($this->cid2);
		$payload .= $this->info->encode();
		echo("Payload: ".$payload."\n");
		return pack("c", self::SOI).$payload.CRC::pylonCRC16($payload).pack("c", self::EOI);
	}
	
	public function decode(HexDataStream $data){
		if(ord($data->readRaw()) != self::SOI){
			echo("Could not find SOI\n");
		}
		out("ProtV:".$data->getHex());
		out("Address:".$data->getHex());
		out("CID1:".$data->getHex());
		out("CID2:".$data->getHex());
		$val = $data->getDec(2);
		$byteLen = ($val & 0x0FFF)/2; //strip crc from length
		out("byteLen: ".$byteLen);
		/*var_dump(decbin($val));
		var_dump(decbin(CRC::pylonInfoLenCRC($byteLen)));
		if($val !== CRC::pylonInfoLenCRC($byteLen)){
			echo("ERROR: CRC MISMATCH!!!!");
		}*/
		out("InfoFlag:".decbin($data->getDec()));
		out("DataAI:".$data->getHex()); //#packs?
		$numCells = $data->getDec();
		out("#Cells: ".$numCells);
		for($i = 0; $i < $numCells; ++$i){
			out("Cell".($i+1)." Voltage: ".$data->getDec(2));
		}
		$numTemps = $data->getDec();
		out("#Temps: ".$numTemps);
		for($i = 0; $i < $numTemps; ++$i){
			out("Temp".($i+1).": ".($data->getDec(2)-2731));
		}
		$packCurrent = $data->getDec(2);
		out("PackCurrent (A): ".$packCurrent*0.1);
		out("PackVoltage (mV): ".$data->getDec(2));
		out("PackResidual_old (mAh): ".$data->getDec(2));
		out("CustomQuantity:".$data->getHex());
		out("PackTotal_old (mAh): ".$data->getDec(2));
		out("BattCycles: ".$data->getDec(2));
		$pR = $data->getDec(3);
		out("PackResidual (mAh): ".$pR);
		$pT = $data->getDec(3);
		out("PackTotal (mAh): ".$pT);
		out("CALC_SOC:".(($pR/$pT)*100)."%");
		$payload = substr($data->rawData(), 1, $data->rawPos()-1);
		if(CRC::pylonCRC16($payload) !== $data->getHex(2)){
			echo("ERROR: CRC MISMATCH!!!!!");
		}else{
			echo("CRC OK");
		}
		if(ord($data->readRaw()) != self::EOI){
			echo("Could not find EOI\n");
		}
		var_dump($data->remaining());
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

class DataInfo{
	//AI Fixed-point
	//AF
	//FLAG
	//RUN_STATE
	//WARN_STATE
}

class CommandInfo{
	//Command Group 1
	//Command Type 1
	//Command ID 1
	//Command Time 7
}

$a = new Frame();
var_dump($a->encode());
var_dump(bin2hex($a->encode()));