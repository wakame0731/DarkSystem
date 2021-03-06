<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\network\protocol;

use pocketmine\network\mcpe\NetworkSession;

class AddBehaviorTreePacket extends PEPacket{
	
	const NETWORK_ID = Info::ADD_BEHAVIOR_TREE_PACKET;
	
	public $unknownString1;

	public function decodePayload(){
		$this->unknownString1 = $this->getString();
	}

	public function encodePayload(){
		$this->putString($this->unknownString1);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddBehaviorTree($this);
	}
	
}
