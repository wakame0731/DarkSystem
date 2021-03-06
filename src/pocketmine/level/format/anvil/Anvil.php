<?php

#______           _    _____           _                  
#|  _  \         | |  /  ___|         | |                 
#| | | |__ _ _ __| | _\ `--. _   _ ___| |_ ___ _ __ ___   
#| | | / _` | '__| |/ /`--. \ | | / __| __/ _ \ '_ ` _ \  
#| |/ / (_| | |  |   </\__/ / |_| \__ \ ||  __/ | | | | | 
#|___/ \__,_|_|  |_|\_\____/ \__, |___/\__\___|_| |_| |_| 
#                             __/ |                       
#                            |___/

namespace pocketmine\level\format\anvil;

use pocketmine\level\format\FullChunk;
use pocketmine\level\format\mcregion\McRegion;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArray;
use pocketmine\nbt\tag\Compound;
use pocketmine\utils\ChunkException;
use pocketmine\utils\Binary;
use pocketmine\nbt\NBT;
use pocketmine\tile\Spawnable;
use pocketmine\level\format\generic\EmptyChunkSection;

class Anvil extends McRegion{
	
	protected $regions = [];
	protected $chunks = [];

	public static function getProviderName(){
		return "anvil";
	}
	
	public static function getProviderOrder(){
		return Anvil::ORDER_YZX;
	}
	
	public static function usesChunkSection(){
		return true;
	}
	
	public static function isValid($path){
		$isValid = (file_exists($path . "/level.dat") and is_dir($path . "/region/"));
		if($isValid){
			$files = glob($path . "/region/*.mc*");
			foreach($files as $f){
				if(strpos($f, ".mcr") !== false){
					$isValid = false;
					break;
				}
			}
		}
		
		return $isValid;
	}
	
	public function requestChunkTask($x, $z){
		$chunk = $this->getChunk($x, $z, false);
		if(!($chunk instanceof Chunk)){
			throw new ChunkException("Invalid Chunk sent");
		}
		
		$tiles = "";
		$nbt = new NBT(NBT::LITTLE_ENDIAN);		
		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$nbt->setData($tile->getSpawnCompound());
				$tiles .= $nbt->write();
			}
		}
		
		$data = array();
		$data['chunkX'] = $x;
		$data['chunkZ'] = $z;
		$data['tiles'] = $tiles;
		/*$data['blocks'] = $chunk->getBlockIdArray();
		$data['data'] = $chunk->getBlockDataArray();
		$data['blockLight'] = $chunk->getBlockLightArray();
		$data['skyLight'] = $chunk->getBlockSkyLightArray();
		$data['heightMap'] = pack("v*", ...$chunk->getHeightMapArray());
		$data['biomeColor'] = $this->convertBiomeColors($chunk->getBiomeColorArray());*/
		$data['isAnvil'] = true;
		$data['chunk'] = $this->getChunkData($chunk);
		
		$this->getLevel()->chunkGenerator->pushMainToThreadPacket(serialize($data));
		return null;
	}
	
	protected function getChunkData($chunk){
		$data = [
			'sections' => [],
			'heightMap' => pack("v*", ...$chunk->getHeightMapArray()),
			'biomeColor' => $this->convertBiomeColors($chunk->getBiomeColorArray())	
		];
		
		$sections = [];
		foreach($chunk->getSections() as $section){
			if($section instanceof EmptyChunkSection){
				continue;
			}
			
			$chunkData = [];
			$chunkData['empty'] = false;
			$chunkData['blocks'] = $section->getIdArray();
			$chunkData['data'] = $section->getDataArray();
			$chunkData['blockLight'] = $section->getLightArray();
			$chunkData['skyLight'] = $section->getSkyLightArray();
			$sections[$section->getY()] = $chunkData;
		}
		
		$sortedSections = [];
		for($y = 0; $y < Chunk::SECTION_COUNT; ++$y){
			if(count($sections) == 0){
				break;
			}
			
			if(isset($sections[$y])){
				$sortedSections[$y] = $sections[$y];
				unset($sections[$y]);				
			}else{
				$sortedSections[$y] = ['empty' => true];
			}
		}
		
		$data['sections'] = $sortedSections;
		return $data;
	}
		
	/**
	 * @param $x
	 * @param $z
	 *
	 * @return RegionLoader
	 */
	protected function getRegion($x, $z){
		return isset($this->regions[$index = Level::chunkHash($x, $z)]) ? $this->regions[$index] : null;
	}

	/**
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk
	 */
	public function getChunk($chunkX, $chunkZ, $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return $this->chunks[$index];
		}else{
			$this->loadChunk($chunkX, $chunkZ, $create);

			return isset($this->chunks[$index]) ? $this->chunks[$index] : null;
		}
	}

	public function setChunk($chunkX, $chunkZ, FullChunk $chunk){
		if(!($chunk instanceof Chunk)){
			throw new ChunkException("Invalid Chunk class");
		}

		$chunk->setProvider($this);

		Anvil::getRegionIndex($chunkX, $chunkZ, $regionX, $regionZ);
		$this->loadRegion($regionX, $regionZ);

		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		
		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)]) and $this->chunks[$index] !== $chunk){
			$this->unloadChunk($chunkX, $chunkZ, false);
		}

		$this->chunks[$index] = $chunk;
	}

	public function getEmptyChunk($chunkX, $chunkZ){
		return Chunk::getEmptyChunk($chunkX, $chunkZ, $this);
	}

	public static function createChunkSection($Y){
		return new ChunkSection(new Compound(null, [
			"Y" => new ByteTag("Y", $Y),
			"Blocks" => new ByteArray("Blocks", str_repeat("\x00", 4096)),
			"Data" => new ByteArray("Data", str_repeat("\x00", 2048)),
			"SkyLight" => new ByteArray("SkyLight", str_repeat("\xff", 2048)),
			"BlockLight" => new ByteArray("BlockLight", str_repeat("\x00", 2048))
		]));
	}

	public function isChunkGenerated($chunkX, $chunkZ){
		if(($region = $this->getRegion($chunkX >> 5, $chunkZ >> 5)) instanceof RegionLoader){
			return $region->chunkExists($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32) and $this->getChunk($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32, true)->isGenerated();
		}

		return false;
	}

	protected function loadRegion($x, $z){
		if(!isset($this->regions[$index = Level::chunkHash($x, $z)])){
			$this->regions[$index] = new RegionLoader($this, $x, $z);
		}
	}
	
	public static function getMaxY(){
		return 256;
	}
	
	public static function getYMask(){
		return 0xff;
	}
	
}
