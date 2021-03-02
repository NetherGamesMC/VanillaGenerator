<?php
declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\utils;

use pocketmine\utils\Random;

class CaveRandom extends Random
{

	public function nextLong(): int
	{
		return (($this->nextSignedInt()) << 32) | $this->nextSignedInt();
	}
}