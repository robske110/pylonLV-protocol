<?php
namespace robske_110\pylonlv\protocol;

/**
 * Helper class for handling a pylon data string
 */
class HexDataStream{
	private string $data;
	
	private int $pos = 0;
	
	public function __construct(string $data){
		$this->data = $data;
	}
	
	public function setPos(int $pos){
		$this->pos = $pos;
	}
	
	public function skipPos(int $len = 1){
		$this->pos += $len*2;
	}
	
	public function rawPos(): int{
		return $this->pos;
	}
	
	public function remaining(): int{
		return strlen($this->data) - $this->pos;
	}
	
	/**
	 * Reads a decimal from the stream.
	 * @param int $byteLen
	 * @return string
	 */
	public function getDec(int $byteLen = 1): int{
		return hexdec($this->getHex($byteLen));
	}
	
	/**
	 * Reads a hex byte (two chars / real bytes) from the stream.
	 * @param int $heyBytes
	 * @return string
	 */
	public function getHex(int $hexBytes = 1): string{
		return $this->readRaw($hexBytes*2);
	}
	
	/**
	 * Reads $bytes bytes (characters)
	 * @param int $bytes
	 * @return string
	 */
	public function readRaw(int $bytes = 1): string{
		$this->pos += $bytes;
		return substr($this->data, $this->pos-$bytes, $bytes);
	}
	
	public function rawData(): string{
		return $this->data;
	}
}