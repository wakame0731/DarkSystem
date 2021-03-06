<?php

#______           _    _____           _                  
#|  _  \         | |  /  ___|         | |                 
#| | | |__ _ _ __| | _\ `--. _   _ ___| |_ ___ _ __ ___   
#| | | / _` | '__| |/ /`--. \ | | / __| __/ _ \ '_ ` _ \  
#| |/ / (_| | |  |   </\__/ / |_| \__ \ ||  __/ | | | | | 
#|___/ \__,_|_|  |_|\_\____/ \__, |___/\__\___|_| |_| |_| 
#                             __/ |                       
#                            |___/

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Enum;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\tile\BrewingStand as TileBrewingStand;
use pocketmine\math\Vector3;

class BrewingStand extends Transparent
{

    protected $id = self::BREWING_STAND_BLOCK;

    public function __construct($meta = 0)
    {
        $this->meta = $meta;
    }

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null)
    {
        if ($block->getSide(Vector3::SIDE_DOWN)->isTransparent() === false) {
            $this->getLevel()->setBlock($block, $this, true, true);
            $nbt = new Compound("", [
                new Enum("Items", []),
                new StringTag("id", Tile::BREWING_STAND),
                new IntTag("x", $this->x),
                new IntTag("y", $this->y),
                new IntTag("z", $this->z)
            ]);
            $nbt->Items->setTagType(NBT::TAG_Compound);
            if ($item->hasCustomName()) {
                $nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
            }

            if ($item->hasCustomBlockData()) {
                foreach ($item->getCustomBlockData() as $key => $v) {
                    $nbt->{$key} = $v;
                }
            }

            Tile::createTile(Tile::BREWING_STAND, $this->getLevel(), $nbt);
            
            return true;
        }
        
        return false;
    }

    public function canBeActivated()
    {
        return true;
    }

    public function getHardness()
    {
        return 0.5;
    }

    public function getResistance()
    {
        return 2.5;
    }

    public function getLightLevel()
    {
        return 1;
    }

    public function getName()
    {
        return "Brewing Stand";
    }

    public function onActivate(Item $item, Player $player = null)
    {
        if ($player instanceof Player) {
            $t = $this->getLevel()->getTile($this);
            if ($t instanceof TileBrewingStand) {
                $brewingStand = $t;
            } else {
                $nbt = new Compound("", [
                    new Enum("Items", []),
                    new StringTag("id", Tile::BREWING_STAND),
                    new IntTag("x", $this->x),
                    new IntTag("y", $this->y),
                    new IntTag("z", $this->z)
                ]);
                $nbt->Items->setTagType(NBT::TAG_Compound);
                $brewingStand = Tile::createTile(Tile::BREWING_STAND, $this->getLevel(), $nbt);
            }
            
            $player->addWindow($brewingStand->getInventory());
        }
        
        return true;
    }

    public function getDrops(Item $item): array
    {
        $drops = [];
        if ($item->isPickaxe() >= Tool::TIER_WOODEN) {
            $drops[] = [Item::BREWING_STAND, 0, 1];
        }
        return $drops;
    }
}
