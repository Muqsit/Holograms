<?php
namespace muqsit\holograms;

use muqsit\holograms\handlers\{
	EventHandler, HologramNetworkHandler, PendingHologramsHandler
};

use pocketmine\command\{Command, CommandSender};
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;

class Holograms extends PluginBase{

	/** @var HologramNetworkHandler[] */
	private $networkHandlers = [];

	public function onEnable() : void{
		foreach($this->getServer()->getLevels() as $level){
			$this->addNetworkHandler($level);
		}

		$pendingHolograms = new PendingHologramsHandler($this);
		new EventHandler($this);

		$this->saveResource("holograms.yml");
		foreach(yaml_parse_file($this->getDataFolder()."holograms.yml") as $levelFolderName => $holograms){
			$level = $this->getServer()->getLevelByName($levelFolderName);
			if($level === null){
				$pendingHolograms->add($levelFolderName, $holograms);
				continue;
			}

			$handler = $this->getNetworkHandler($level->getId());
			foreach($holograms as $hologram){
				$handler->addHologram(Hologram::fromArray($hologram));
			}
		}
	}

	public function onDisable() : void{
		$holograms = [];
		foreach($this->networkHandlers as $handler){
			$level = $handler->getLevelName();
			foreach($handler->getHolograms() as $hologram){
				$holograms[$level][] = $hologram->toArray();
			}
		}

		yaml_emit_file($this->getDataFolder()."holograms.yml", $holograms);
	}

	public function addNetworkHandler(Level $level, ?HologramNetworkHandler $networkHandler = null) : void{
		if($networkHandler === null){
			$networkHandler = new HologramNetworkHandler($level);
		}
		$this->networkHandlers[$networkHandler->getIdentifier()] = $networkHandler;
	}

	public function getNetworkHandler(int $identifier, bool $create = false) : ?HologramNetworkHandler{
		$handler = $this->networkHandlers[$identifier] ?? null;
		if($handler === null){
			if(!$create){
				return null;
			}

			$level = $this->getServer()->getLevel($identifier);
			if($level === null){
				throw new \InvalidArgumentException("identifier must be a valid level id");
			}

			$this->addNetworkHandler($level);
			$handler = $this->getNetworkHandler($identifier);
		}

		return $handler;
	}

	public function onHandlerLevelChange(HologramNetworkHandler $handler, int $oldidentifier) : void{
		unset($this->networkHandlers[$oldidentifier]);
		$this->networkHandlers[$handler->getIdentifier()] = $handler;
	}

	public function onCommand(CommandSender $issuer, Command $cmd, string $label, array $args) : bool{
		if(!empty($args)){
			switch($args[0]){
				case "add":
					if(isset($args[1])){
						$handler = $this->getNetworkHandler($issuer->getLevel()->getId(), true);
						$handler->addHologram(new Hologram($issuer, $args[1]));
						$issuer->sendMessage("Added hologram '{$args[1]}'.");
					}
					break;
				case "removewand":
					break;
			}
		}
		return false;
	}
}