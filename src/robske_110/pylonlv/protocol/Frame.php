<?php
namespace robske_110\pylonlv\protocol;

use robske_110\pylonlv\protocol\CRC;

function hexStr(string $data, $len = 2){
	return strtoupper(str_pad(dechex($data), $len, "0", STR_PAD_LEFT));
}
#var_dump(hexStr(50));
#var_dump(hexStr(crc16_pylon($command)));

$b = new Frame();
/*$b->decode("~200246040000FDAE
");*/
$b->decode("~20024600F07A11020F0CDE0CDC0CE20CDE0CE30CE30CE30CDF0CD80CDD0CE00CDE0CE20CE10CDF050B870B870B870B870B870000C117FFFF04FFFF00000070BC012110E1E7
");
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
		return pack("c", self::SOI).$payload.CRC::crc16_pylon($payload).pack("c", self::EOI);
	}
	
	public function decode(string $data){
		if(unpack("c", $data[0])[1] != self::SOI){
			echo("Could not find SOI\n");
		}
		if(unpack("c", $data[strlen($data)-1])[1] != self::EOI){
			echo("Could not find EOI\n");
		}
		$pos = -1;
		out("ProtV:".readByte($data, $pos));
		out("Address:".readByte($data, $pos));
		out("CID1:".readByte($data, $pos));
		out("CID2:".readByte($data, $pos));
		$val = hexdec(readByte($data, $pos).readByte($data, $pos));
		$byteLen = ($val & 0x0FFF)/2;
		out("byteLen: ".$byteLen);
		out("InfoFlag:".decbin(readByte($data, $pos)));
		out("DataAI:".readByte($data, $pos)); //#packs?
		$numCells = hexdec(readByte($data, $pos));
		out("#Cells: ".$numCells);
		for($i = 0; $i < $numCells; ++$i){
			out("Cell".($i+1)." Voltage: ".hexdec(readByte($data, $pos).readByte($data,$pos)));
		}
		$numTemps = hexdec(readByte($data, $pos));
		out("#Temps: ".$numTemps);
		for($i = 0; $i < $numTemps; ++$i){
			out("Temp".($i+1).": ".(hexdec(readByte($data, $pos).readByte($data,$pos))-2731));
		}
		$packCurrent = hexdec(readByte($data, $pos).readByte($data, $pos));
		out("PackCurrent (A): ".$packCurrent*0.1);
		out("PackVoltage (mV): ".hexdec(readByte($data, $pos).readByte($data, $pos)));
		out("PackResidual_old (mAh): ".hexdec(readByte($data, $pos).readByte($data, $pos)));
		out("CustomQuantity:".readByte($data, $pos));
		out("PackTotal_old (mAh): ".hexdec(readByte($data, $pos).readByte($data, $pos)));
		out("BattCycles: ".hexdec(readByte($data, $pos).readByte($data, $pos)));
		$pR = hexdec(readByte($data, $pos).readByte($data, $pos).readByte($data, $pos));
		out("PackResidual (mAh): ".$pR);
		$pT = hexdec(readByte($data, $pos).readByte($data, $pos).readByte($data, $pos));
		out("PackTotal (mAh): ".$pT);
		out("CALC_SOC:".(($pR/$pT)*100)."%");
		$payload = substr($data, 1, $pos+1);
		if(crc16_pylon($payload) !== readByte($data, $pos).readByte($data, $pos)){
			echo("ERROR: CRC MISMATCH!!!!!");
		}else{
			echo("CRC OK");
		}
	}
}

class FrameInfo{
	
	public function encode(): string{
		//retarded crc for pylon length (WHY A CRC IN A FRAME THAT ALREADY HAS A CRC)
		$len = 2;
		$crc = ($len >> 8 & 0xF) + ($len >> 4 & 0xF) + ($len & 0xF); //12bit len number, add each 4 bit as a number together
		$crc = (~($crc % 16) + 1) & 0xF;
		$lengthWithCRC = $len + ($crc << 12);
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