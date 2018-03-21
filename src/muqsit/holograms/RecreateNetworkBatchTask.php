<?php
namespace muqsit\holograms;

use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class RecreateNetworkBatchTask extends AsyncTask{

	/** @var int */
	private $networkId;

	/** @var string */
	private $holograms;

	public function __construct(HologramNetworkHandler $networkHandler){
		$this->networkId = $networkHandler->getIdentifier();
		$this->holograms = serialize($networkHandler->getHolograms());
	}

	public function onRun() : void{
		$spk = new BatchPacket();
		$dpk = new BatchPacket();

		foreach(unserialize($this->holograms) as $hologram){
			$spk->addPacket($hologram->toPacket());
			$dpk->addPacket($hologram->getDespawnPacket());
		}

		$this->setResult([$spk, $dpk]);
	}

	public function onCompletion(Server $server) : void{
		[$spawnPacket, $despawnPacket] = $this->getResult();
		$server->getPluginManager()->getPlugin("Holograms")->getNetworkHandler($this->networkId)->onBatchRecreate($spawnPacket, $despawnPacket);
	}
}