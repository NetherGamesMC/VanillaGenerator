<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator;

use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use Random;

interface Populator
{

	public function populate(ChunkManager $world, Random $random, int $chunk_x, int $chunk_z, Chunk $chunk): void;
}