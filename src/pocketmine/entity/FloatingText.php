<?php

#______           _    _____           _                  
#|  _  \         | |  /  ___|         | |                 
#| | | |__ _ _ __| | _\ `--. _   _ ___| |_ ___ _ __ ___   
#| | | / _` | '__| |/ /`--. \ | | / __| __/ _ \ '_ ` _ \  
#| |/ / (_| | |  |   </\__/ / |_| \__ \ ||  __/ | | | | | 
#|___/ \__,_|_|  |_|\_\____/ \__, |___/\__\___|_| |_| |_| 
#                             __/ |                       
#                            |___/

namespace pocketmine\entity;

//use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;

class FloatingText extends Entity{

	protected $title;
	protected $text;

	protected function initEntity(){
		parent::initEntity();

		$this->setTitle($this->namedtag["Title"] ?? "");
		$this->setText($this->namedtag["Text"] ?? "");

		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
		$this->setScale(0.01);
	}

	public function getTitle(){
		return $this->title;
	}

	public function setTitle($title){
		$this->title = $title;

		$this->updateNameTag();
	}

	public function getText(){
		return $this->text;
	}

	public function setText($text){
		$this->text = $text;

		$this->updateNameTag();
	}

	private function updateNameTag(){
		$this->setNameTag($this->title . ($this->text !== "" ? "\n$this->text" : ""));
	}

	public function saveNBT(){
		parent::saveNBT();

		$this->namedtag->Title = new StringTag("Title", $this->title);
		$this->namedtag->Text = new StringTag("Text", $this->text);
	}

	public function onUpdate($currentTick){
		return true;
	}

	public function canCollideWith(Entity $entity){
		return true;
	}

	public function spawnTo(Player $player){
		$pk = new AddPlayerPacket();
		$pk->uuid = UUID::fromRandom();
		$pk->username = "";
		$pk->eid = $this->id;
		$pk->position = $this->asVector3();
		$pk->item = Item::get(Item::AIR, 0, 0);
		$pk->metadata = $this->dataProperties;

		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}
