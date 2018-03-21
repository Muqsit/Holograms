<?php
namespace muqsit\holograms\handlers;

use muqsit\holograms\{Hologram, Holograms};

use pocketmine\event\level\{LevelLoadEvent, LevelUnloadEvent};
use pocketmine\event\Listener;

class PendingHologramsHandler implements Listener{

	/** @var Hologram[][] */
	private $pending = [];

	/** @var int[] */
	private $handlerIds = [];

	/** @var Holograms */
	private $plugin;

	public function __construct(Holograms $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function add(string $levelFolderName, array $holograms) : void{
		if(isset($this->pending[$levelFolderName])){
			$holograms = array_merge($this->pending[$levelFolderName], $holograms);
		}
		$this->pending[$levelFolderName] = $holograms;
	}

	public function onLevelLoad(LevelLoadEvent $event) : void{
		$level = $event->getLevel();
		$folderName = $level->getFolderName();

		if(isset($this->handlerIds[$folderName])){
			$handler = $this->plugin->getNetworkHandler($this->handlerIds[$folderName]);
			$handler->setLevel($level, $this->plugin);
			unset($this->handlerIds[$folderName]);
		}

		if(isset($this->pending[$folderName])){

			$this->plugin->addNetworkHandler($level);
			$handler = $this->plugin->getNetworkHandler($level->getId());

			foreach($this->pending[$folderName] as $hologram){
				$handler->addHologram(Hologram::fromArray($hologram));
			}
			unset($this->pending[$folderName]);
		}
	}

	public function onLevelUnload(LevelUnloadEvent $event) : void{
		$level = $event->getLevel();
		if($this->plugin->getNetworkHandler($level->getId()) !== null){
			$this->handlerIds[$level->getFolderName()] = $level->getId();
		}
	}
}