<?php
namespace muqsit\holograms;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\{AddPlayerPacket, RemoveEntityPacket};
use pocketmine\utils\UUID;

class Hologram extends Vector3{

	/** @var string */
	private $text;

	/** @var int */
	private $identifier;

	public static function fromArray(array $array) : Hologram{
		if(isset($array["x"], $array["y"], $array["z"], $array["text"])){
			if(!is_numeric($array["x"]) || !is_numeric($array["y"]) || !is_numeric($array["z"]) || !is_string($array["text"])){
				throw new \InvalidArgumentException("'x', 'y' and 'z' keys must be numeric and 'text' key must be a string.");
			}

			["x" => $x, "y" => $y, "z" => $z, "text" => $text] = $array;
			return new Hologram(new Vector3($x, $y, $z), $text);
		}

		throw new \Error("Given array must contain the keys 'x', 'y', 'z' and 'text', but contains (".implode(", ", array_keys($array)).") keys.");
	}

	public function __construct(Vector3 $pos, string $text){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->text = $text;
		$this->identifier = Entity::$entityCount++;
	}

	public function getIdentifier() : int{
		return $this->identifier;
	}

	public function toPacket() : AddPlayerPacket{
		return Hologram::createBasePacket($this->asVector3(), $this->text, $this->identifier);
	}

	public function toArray() : array{
		return [
			"x" => $this->x,
			"y" => $this->y,
			"z" => $this->z,
			"text" => $this->text
		];
	}

	public function getDespawnPacket() : RemoveEntityPacket{
		$pk = new RemoveEntityPacket();
		$pk->entityUniqueId = $this->identifier;
		return $pk;
	}

	public static function createBasePacket(Vector3 $pos, string $text, ?int $entityRuntimeId = null) : AddPlayerPacket{
		$pk = new AddPlayerPacket();
		$pk->username = "";
		$pk->uuid = UUID::fromRandom();
		$pk->entityRuntimeId = $entityRuntimeId ?? Entity::$entityCount++;
		$pk->position = $pos;
		$pk->item = Item::get(Item::AIR, 0, 0);

		$pk->metadata[Entity::DATA_NAMETAG] = [Entity::DATA_TYPE_STRING, $text];
		$pk->metadata[Entity::DATA_BOUNDING_BOX_WIDTH] = [Entity::DATA_TYPE_FLOAT, 0.2];
		$pk->metadata[Entity::DATA_BOUNDING_BOX_HEIGHT] = [Entity::DATA_TYPE_FLOAT, 0.2];
		$pk->metadata[Entity::DATA_SCALE] = [Entity::DATA_TYPE_FLOAT, 0];

		return $pk;
	}
}