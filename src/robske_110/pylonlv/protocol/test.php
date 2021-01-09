<?php
declare(strict_types=1);

use robske_110\Logger\Logger;
use robske_110\pylonlv\protocol\command\AnalogValue;
use robske_110\pylonlv\protocol\command\ProtocolVersion;
use robske_110\pylonlv\protocol\HexDataStream;
use robske_110\pylonlv\PylonSerial;

require("../../../Autoloader.php");
Logger::init();


$a = new \robske_110\pylonlv\protocol\command\AnalogValue(0x02);
#var_dump(bin2hex($a->encode()));
$pylonSerial = new PylonSerial("/dev/cu.SLAB_USBtoUART");
$pylonSerial->open();
$pylonSerial->send($a->encode());

$a->decode(new HexDataStream($pylonSerial->readUntil()));
echo($a);


exit;
$b = new AnalogValue(0x02);
/*$b->decode("~200246040000FDAE
");*/
$b->decode(new HexDataStream("~20024600F07A11020F0CE00CDF0CE50CDF0CE40CE60CE60CE00CD90CDE0CE40CE00CE40CE40CE3050B9B0B870B870B870B870004C139FFFF04FFFF00000070BC012110E209\r"));
/*$b->decode("~20014600C06E11010F0D450D440D450D440D450D440D3E0D450D4A0D4A0D4B0D4A0D4A0D4A0D4A050BC30BC30BC30BCD0BCD0000C725BF6802C3500002E553
");*/
echo("\n\n\n");