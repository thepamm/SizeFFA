<?php

declare(strict_types=1);

namespace ThePammYT\SizeFFA;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\{PlayerInteractEvent, PlayerMoveEvent, PlayerRespawnEvent, PlayerDeathEvent, PlayerQuitEvent , PlayerItemHeldEvent};
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};

class Main extends PluginBase implements Listener{

		public $dat;
		public $match = [];

	public function onEnable() : void{
		$this->getLogger()->info("Plugin activado");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "sffa":
				if( !isset($args[0]) ){
					$sender->sendMessage("§cUtilize /sffa { create , help , join , exit }");
					return false;
				}
				if( strtolower($args[0]) == "create" ){
					if( $sender->hasPermission("sizeffa.command.op") ){
						@mkdir($this->getDataFolder());
						$this->dat = new Config($this->getDataFolder()."config.yml", Config::YAML);
						$this->dat->set("world", $sender->getLevel()->getName());
						$this->dat->set("x", $sender->x);
						$this->dat->set("y", $sender->y);
						$this->dat->set("z", $sender->z);
						$this->dat->save();
						$sender->sendMessage("§aArena Creada Correctamente!");
						return true;
					}else{
						$sender->sendMessage("§cyou do not have permission to use this command.");
						return true;
					}

				}
				if( strtolower($args[0]) == "help" ){
					$sender->sendMessage("§aHelp List 1/1");
					$sender->sendMessage("§a> /sffa create | Create SizeFFA Spawn");
					$sender->sendMessage("§a> /sffa help   | SizeFFA Help");
					$sender->sendMessage("§a> /sffa join   | Join to SizeFFA");
					$sender->sendMessage("§a> /sffa create | Exit SizeFFA");

				}
				if( strtolower($args[0]) == "join" ){
					$player = $sender;
					
					$dat = new Config($this->getDataFolder()."config.yml", Config::YAML);
					if( isset( $dat->get("x") == true ) ){
						$this->match[$player->getName()] = true;
						$player->sendMessage("§b/sffa exit |> exit SizeFFA");
						$player->teleport(new Position($dat->get("x"), $dat->get("y"), $dat->get("z"), $this->getServer()->getLevelByName($dat->get("world"))));
						$this->ckit($player);
						$player->sendTip("§l§aSize FFA§r\n\n\n");
						$player->setGamemode(0);
					}else{
						$player->sendMessage("§bSFFA has not been created, use /sffa create");
					}
					
					//sound...

					
				}
				if( strtolower($args[0]) == "exit" ){
					$player = $sender;
					if( isset( $this->match[$player->getName()] ) ){
					$player->sendMessage("§bhas left SizeFFA");
					$player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
					unset( $this->match[$player->getName()] );
					$player->setScale(1);
					$player->getInventory()->clearAll();
					$player->getArmorInventory()->clearAll();

					}else {
					$player->sendMessage("§cyou are not in SizeFFA.");
					}
				}
				return true;
			default:
				return false;
		}
	}

	public function wRespawn(PlayerRespawnEvent $e){
		$player = $e->getPlayer();
		if( isset($this->match[$player->getName()]) ){
			$dat = new Config($this->getDataFolder()."config.yml", Config::YAML);
			$e->setRespawnPosition(new Position($dat->get("x"), $dat->get("y"), $dat->get("z"), $this->getServer()->getLevelByName($dat->get("world"))));
			$this->ckit($player);
		}else if( !isset($this->match[$player->getName()]) ){

		}
	}

	public function wDeath(PlayerDeathEvent $e){
		$player = $e->getPlayer();
		if( isset($this->match[$player->getName()]) ){
			$player->setScale(1);
			$e->setDrops([]);
		}
		$causa = $e->getEntity()->getLastDamageCause();
		if($causa instanceof EntityDamageByEntityEvent){
			$attakr = $causa->getDamager();
			if( isset($this->match[$attakr->getName()]) ){
			$attakr->setScale( $attakr->getScale() + 0.1 );
			$attakr->addTitle("§c+1 Kill");
			$this->getServer()->getLevelByName( $attakr->getLevel()->getName() )->broadcastLevelSoundEvent(new Vector3($attakr->x,$attakr->y,$attakr->z), LevelSoundEventPacket::SOUND_NOTE);
			}
		}
	}

	public function wQuit(PlayerQuitEvent $e){
		$player = $e->getPlayer();
		if( isset($this->match[$player->getName()]) ){
			unset( $this->match[$player->getName()] );
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
		}
	}

	public function onDisable() : void{
		$this->getLogger()->info("Plugin Desactivado");
	}

	public function ckit(Player $player){
		$casco = Item::get(Item::DIAMOND_HELMET);
		$pechera = Item::get(Item::DIAMOND_CHESTPLATE);
		$pants = Item::get(Item::DIAMOND_LEGGINGS);
		$botas = Item::get(Item::DIAMOND_BOOTS);
		$enchantment = Enchantment::getEnchantment(0);
		$enchInstance = new EnchantmentInstance($enchantment, 1);
		$casco->addEnchantment($enchInstance);
		$pechera->addEnchantment($enchInstance);
		$pants->addEnchantment($enchInstance);
		$botas->addEnchantment($enchInstance);

		$player->getInventory()->clearAll();
		$player->getInventory()->setItem(0, Item::get(276, 0, 1));
		$player->getInventory()->addItem(Item::get(466, 0, 8));
		$player->getArmorInventory()->setHelmet($casco);
		$player->getArmorInventory()->setChestplate($pechera);
		$player->getArmorInventory()->setLeggings($pants);
		$player->getArmorInventory()->setBoots($botas);
	}
}
