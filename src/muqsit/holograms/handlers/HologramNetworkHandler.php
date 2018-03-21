<?php
namespace muqsit\holograms\handlers;

use muqsit\holograms\{Hologram, Holograms};

use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;

class HologramNetworkHandler{

	/** @var int */
	private $levelId;

	/** @var string */
	private $levelName;

	/** @var Hologram[] */
	private $holograms = [];

	/** @var BatchPacket|null */
	private $despawnPacket;

	/** @var BatchPacket|null */
	private $spawnPacket;

	/** @var BatchPacket|null */
	private $despawnPacketCache;

	/** @var BatchPacket|null */
	private $spawnPacketCache;

	/** @var int[] */
	private $spawnWaitingQueue = [];

	/** @var int[] */
	private $despawnWaitingQueue = [];

	public function __construct(Level $level){
		$this->setLevel($level);
		$this->spawnPacket = $this->spawnPacketCache = new BatchPacket();
		$this->despawnPacket = $this->despawnPacketCache = new BatchPacket();
	}

	public function setLevel(Level $level, ?Holograms $plugin = null) : void{
		if($this->levelId !== null && $plugin === null){
			throw new \Error("plugin cannot be null if the HologramNetworkHandler level is being swapped.");
		}

		$oldId = $this->levelId;

		$this->levelId = $level->getId();
		$this->levelName = $level->getFolderName();

		if($plugin !== null){
			$plugin->onHandlerLevelChange($this, $oldId);
		}
	}

	public function getIdentifier() : int{
		return $this->levelId;
	}

	public function getLevel() : Level{
		return Server::getInstance()->getLevel($this->levelId);
	}

	public function getLevelName() : string{
		return $this->levelName;
	}

	public function send(Player $player) : void{
		if($this->spawnPacket === null){//batch is being recreated asynchronously
			$this->spawnWaitingQueue[$player->getLoaderId()] = null;
		}elseif($this->spawnPacketCache->buffer !== ""){
			$player->dataPacket($this->spawnPacketCache);
		}
	}

	public function unsend(Player $player) : void{
		if($this->despawnPacket === null){
			$this->despawnWaitingQueue[$player->getRawUniqueId()] = null;
		}elseif($this->despawnPacketCache->buffer !== ""){
			$player->dataPacket($this->despawnPacketCache);
		}
	}

	public function addHologram(Hologram $hologram) : void{
		$this->holograms[$hologram->getIdentifier()] = $hologram;

		$packet = $hologram->toPacket();
		$this->spawnPacket->addPacket($packet);

		$this->spawnPacketCache->payload = $this->spawnPacket->payload;
		$this->spawnPacketCache->encode();

		foreach($this->getLevel()->getPlayers() as $player){
			$player->dataPacket($packet);
		}

		$this->despawnPacket->addPacket($hologram->getDespawnPacket());
		$this->despawnPacketCache->payload = $this->despawnPacket->payload;
		$this->despawnPacketCache->encode();
	}

	public function removeHologram(Hologram $hologram) : void{
		if(isset($this->holograms[$id = $hologram->getIdentifier()])){
			unset($this->holograms[$id]);
			$this->recreateBatch();
		}
	}

	public function getHolograms() : array{
		return $this->holograms;
	}

	private function recreateBatch() : void{
		if(!empty($this->holograms)){
			Server::getInstance()->getScheduler()->scheduleAsyncTask(new RecreateNetworkBatchTask($this));
			$this->spawnPacket = null;
			$this->despawnPacket = null;
		}
	}

	public function onBatchRecreate(BatchPacket $spawnPacket, BatchPacket $despawnPacket) : void{
		$this->spawnPacket = $spawnPacket;
		$this->despawnPacket = $despawnPacket;

		$this->spawnPacketCache->payload = $spawnPacket->payload;
		$this->spawnPacketCache->encode();

		$this->despawnPacketCache->payload = $despawnPacket->payload;
		$this->despawnPacketCache->encode();

		if(!empty($this->spawnWaitingQueue)){
			foreach(array_intersect_key($this->getLevel()->getLoaders(), $this->spawnWaitingQueue) as $player){
				$player->dataPacket($spawnPacket);
			}
			$this->spawnWaitingQueue = [];
		}

		if(!empty($this->depawnWaitingQueue)){
			foreach(array_intersect_key(Server::getInstance()->getOnlinePlayers(), $this->despawnWaitingQueue) as $player){
				$player->dataPacket($despawnPacket);
			}
			$this->despawnWaitingQueue = [];
		}
	}
}