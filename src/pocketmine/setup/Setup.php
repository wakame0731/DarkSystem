<?php

#______           _    _____           _                  
#|  _  \         | |  /  ___|         | |                 
#| | | |__ _ _ __| | _\ `--. _   _ ___| |_ ___ _ __ ___   
#| | | / _` | '__| |/ /`--. \ | | / __| __/ _ \ '_ ` _ \  
#| |/ / (_| | |  |   </\__/ / |_| \__ \ ||  __/ | | | | | 
#|___/ \__,_|_|  |_|\_\____/ \__, |___/\__\___|_| |_| |_| 
#                             __/ |                       
#                            |___/

namespace pocketmine\setup;

use pocketmine\Translate;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;

class Setup{
	
	const DEFAULT_NAME = "DarkSystem Server";
	const DEFAULT_PORT = 19132;
	const DEFAULT_MEMORY = 256;
	const DEFAULT_PLAYERS = 100;
	const DEFAULT_GAMEMODE = 0;
	const DEFAULT_LEVEL_NAME = "world";
	const DEFAULT_LEVEL_TYPE = "FLAT";
	
	const LEVEL_TYPES = [
		"DEFAULT",
		"FLAT",
		"NORMAL",
		"HELL",
		"NETHER",
		"ENDER",
		"VOID",
	];

	private $defaultLang;

	public function __construct(){
		echo "[*] DarkSystem Set-up Wizard\n";
		echo "[*] Language:\n";
		foreach(SetupLanguage::$languages as $short => $native){
			echo " $native => $short\n";
		}
		do{
			echo "[?] Language (eng): ";
			$lang = strtolower($this->getInput("eng"));
			if(!isset(SetupLanguage::$languages[$lang])){
				echo "[!] Language Not Found!\n";
				$lang = false;
			}
			$this->defaultLang = $lang;
		}while($lang == false);
		$this->lang = new SetupLanguage($lang);
		
		echo "[*] " . $this->lang->language_has_been_selected . "\n";

		if(!$this->showLicense()){
			@\pocketmine\kill(getmypid());
			exit(-1);
			exit(-1);
			exit(-1);
		}

		echo "[?] " . $this->lang->skip_installer . " (y/N): ";
		if(strtolower($this->getInput()) === "y"){
			return;
		}
		
		echo "\n";
		$this->welcome();
		$this->generateBaseConfig();
		$this->generateUserFiles();
		$this->networkFunctions();
		$this->endWizard();
	}

	public function getDefaultLang(){
		return $this->defaultLang;
	}

	private function showLicense(){
		echo $this->lang->welcome_to_pocketmine . "\n";
		if($this->lang == "tur"){
			echo <<<LISANS

  Bu Sunucu Dosyaları Ücretsizdir, Çoğaltılabilir. LGPL Lisansı Altında Lisanslanmıştır.

LISANS;
		}else{
			echo <<<LICENSE

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

LICENSE;
		}
		echo "\n[?] " . $this->lang->accept_license . " (y/N): ";
		if(strtolower($this->getInput("n")) != "y"){
			echo "[!] " . $this->lang->you_have_to_accept_the_license . "\n";
			sleep(4);
			return false;
		}

		return true;
	}

	private function welcome(){
		echo "[*] " . $this->lang->setting_up_server_now . "\n";
		echo "[*] " . $this->lang->default_values_info . "\n";
		echo "[*] " . $this->lang->server_properties . "\n";
	}

	private function generateBaseConfig(){
		if($this->lang == "tur"){
			$config = new Config(\pocketmine\DATA . "sunucu.properties", Config::PROPERTIES);
		}else{
			$config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
		}
		echo "[?] " . $this->lang->name_your_server . " (" . self::DEFAULT_NAME . "): ";
		$server_name = $this->getInput(self::DEFAULT_NAME);
		$config->set("server-name", $server_name);
		$config->set("motd", $server_name);
		echo "[*] " . $this->lang->port_warning . "\n";
		do{
			echo "[?] " . $this->lang->server_port . " (" . self::DEFAULT_PORT . "): ";
			$port = (int) $this->getInput(self::DEFAULT_PORT);
			if($port <= 0 or $port > 65535){
				echo "[!] " . $this->lang->invalid_port . "\n";
			}
		}while($port <= 0 or $port > 65535);
		$config->set("server-port", $port);
		
		echo "[*] " . $this->lang->online_mode_info . "\n";
		echo "[?] " . $this->lang->online_mode . " (y/N): ";
		$config->set("online-mode", strtolower($this->getInput("y")) == "y");
		
		echo "[?] " . $this->lang->level_name . " (" . self::DEFAULT_LEVEL_NAME . "): ";
		$config->set("level-name", $this->getInput(self::DEFAULT_LEVEL_NAME));
		do{
			echo "[?] " . $this->lang->level_type . " (" . self::DEFAULT_LEVEL_TYPE . "): ";
			$type = strtoupper((string) $this->getInput(self::DEFAULT_LEVEL_TYPE));
			if(!in_array($type, self::LEVEL_TYPES)){
				echo "[!] " . $this->lang->invalid_level_type . "\n";
			}
		}while(!in_array($type, self::LEVEL_TYPES));
		$config->set("level-type", $type);
		
		echo "[*] " . $this->lang->gamemode_info . "\n";
		do{
			echo "[?] " . $this->lang->default_gamemode . ": (" . self::DEFAULT_GAMEMODE . "): ";
			$gamemode = (int) $this->getInput(self::DEFAULT_GAMEMODE);
		}while($gamemode < 0 or $gamemode > 3);
		$config->set("gamemode", $gamemode);
		echo "[?] " . $this->lang->max_players . " (" . self::DEFAULT_PLAYERS . "): ";
		$config->set("max-players", (int) $this->getInput(self::DEFAULT_PLAYERS));
		echo "[*] " . $this->lang->spawn_protection_info . "\n";
		echo "[?] " . $this->lang->spawn_protection . " (Y/n): ";
		if(strtolower($this->getInput("y")) == "n"){
			$config->set("spawn-protection", -1);
		}else{
			$config->set("spawn-protection", 16);
		}
		echo "[?] " . $this->lang->announce_player_achievements . " (y/N): ";
		if(strtolower($this->getInput("n")) === "y"){
			$config->set("announce-player-achievements", "on");
		}else{
			$config->set("announce-player-achievements", "off");
		}
		$config->save();
	}

	private function generateUserFiles(){
		echo "[*] " . $this->lang->op_info . "\n";
		echo "[?] " . $this->lang->op_who . ": ";
		$op = strtolower($this->getInput(""));
		if($op === ""){
			echo "[!] " . $this->lang->op_warning . "\n";
		}
		if($this->lang == "tur"){
			$ops = new Config(\pocketmine\DATA . "yoneticiler.json", Config::ENUM);
		}else{
			$ops = new Config(\pocketmine\DATA . "ops.json", Config::ENUM);
		}
		$ops->set($op, true);
		$ops->save();
		echo "[*] " . $this->lang->whitelist_info . "\n";
		echo "[?] " . $this->lang->whitelist_enable . " (y/N): ";
		if($this->lang == "tur"){
			$config = new Config(\pocketmine\DATA . "sunucu.properties", Config::PROPERTIES);
		}else{
			$config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
		}
		if(strtolower($this->getInput("n")) === "y"){
			echo "[!] " . $this->lang->whitelist_warning . "\n";
			$config->set("white-list", true);
		}else{
			$config->set("white-list", false);
		}
		$config->save();
	}

	private function networkFunctions(){
		if($this->lang == "tur"){
			$config = new Config(\pocketmine\DATA . "sunucu.properties", Config::PROPERTIES);
		}else{
			$config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
		}
		echo "[!] " . $this->lang->query_warning1 . "\n";
		echo "[!] " . $this->lang->query_warning2 . "\n";
		echo "[?] " . $this->lang->query_disable . " (y/N): ";
		if(strtolower($this->getInput("n")) === "y"){
			$config->set("enable-query", false);
		}else{
			$config->set("enable-query", true);
		}

		echo "[*] " . $this->lang->rcon_info . "\n";
		echo "[?] " . $this->lang->rcon_enable . " (y/N): ";
		if(strtolower($this->getInput("n")) === "y"){
			$config->set("enable-rcon", true);
			$password = substr(base64_encode(random_bytes(20)), 3, 10);
			$config->set("rcon.password", $password);
			echo "[*] " . $this->lang->rcon_password . ": " . $password . "\n";
		}else{
			$config->set("enable-rcon", false);
		}
		
		$config->save();
		
		echo "[*] " . $this->lang->ip_get . "\n";

		$externalIP = Utils::getIP();
		$internalIP = gethostbyname(trim(`hostname`));

		echo "[!] " . $this->lang->get("ip_warning", ["{{EXTERNAL_IP}}", "{{INTERNAL_IP}}"], [$externalIP, $internalIP]) . "\n";
		echo "[!] " . $this->lang->ip_confirm;
		$this->getInput();
	}

	private function endWizard(){
		echo "[*] " . $this->lang->you_have_finished . "\n";
		echo "[*] " . $this->lang->pocketmine_plugins . "\n";
		echo "[*] " . $this->lang->pocketmine_will_start . "\n\n\n";
		sleep(4);
	}

	private function getInput($default = ""){
		$input = trim(fgets(STDIN));

		return $input === "" ? $default : $input;
	}
}
