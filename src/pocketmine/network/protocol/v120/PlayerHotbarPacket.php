<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class PlayerHotbarPacket extends PEPacket{
	
	const NETWORK_ID = Info120::PLAYER_HOTBAR_PACKET;
	const PACKET_NAME = "PLAYER_HOTBAR_PACKET";
	
	public $selectedSlot;
	public $slotsLink;
	
	public function decode($playerProtocol){
		
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->selectedSlot);
		$slotsNum = count($this->slotsLink);
		$this->putVarInt($slotsNum);
		for($i = 0; $i < $slotsNum; $i++){
			$this->putVarInt($this->slotsLink[$i]);
		}
		$this->putByte(false);
	}
}
