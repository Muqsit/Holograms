<?php
namespace muqsit\holograms\handlers;

use muqsit\holograms\Holograms;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\Player;

class EventHandler implements Listener{

	/** @var Holograms */
	private $plugin;

	public function __construct(Holograms $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function onEntityLevelChange(EntityLevelChangeEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			$handler = $this->plugin->getNetworkHandler($event->getOrigin()->getId());
			if($handler !== null){
				$handler->unsend($player);
			}

			$handler = $this->plugin->getNetworkHandler($event->getTarget()->getId());
			if($handler !== null){
				$handler->send($player);
			}
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$handler = $this->plugin->getNetworkHandler($player->getLevel()->getId());
		if($handler !== null){
			$handler->send($player);
		}
	}
}