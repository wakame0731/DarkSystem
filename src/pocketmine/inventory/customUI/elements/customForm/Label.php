<?php

namespace pocketmine\inventory\customUI\elements\customForm;

use pocketmine\inventory\customUI\elements\UIElement;

class Label extends UIElement{
	
	/**
	 * 
	 * @param string $text
	 */
	public function __construct($text) {
		$this->text = $text;
	}
	
	/**
	 * 
	 * @return array
	 */
	final public function getDataToJson() {
		return [
			"type" => "label",
			"text" => $this->text
		];
	}

	/**
	 * @notice Value for Label always null
	 * 
	 * @param null $value
	 * @param Player $player
	 */
	final public function handle($value, $player) {
		
	}
	
}
