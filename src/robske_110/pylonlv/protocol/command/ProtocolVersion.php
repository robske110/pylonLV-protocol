<?php
declare(strict_types=1);
namespace robske_110\pylonlv\protocol\command;

use robske_110\pylonlv\protocol\CID2;

class ProtocolVersion extends Command{
	public function __construct(?int $addr = null){
		parent::__construct(CID2::PROTOCOL_VERSION, $addr);
	}
}