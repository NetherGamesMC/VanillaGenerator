<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\biomegrid;

use muqsit\vanillagenerator\generator\biomegrid\utils\MapLayerPair;
use muqsit\vanillagenerator\generator\Environment;
use muqsit\vanillagenerator\generator\overworld\biome\BiomeIds;
use muqsit\vanillagenerator\generator\overworld\WorldType;
use Random;

abstract class MapLayer
{

	public static function initialize(int $seed, int $environment, string $world_type): MapLayerPair
	{
		if ($environment === Environment::OVERWORLD && $world_type === WorldType::FLAT) {
			return new MapLayerPair(new ConstantBiomeMapLayer($seed, BiomeIds::PLAINS), null);
		}

		if ($environment === Environment::NETHER) {
			return new MapLayerPair(new ConstantBiomeMapLayer($seed, BiomeIds::HELL), null);
		}

		if ($environment === Environment::THE_END) {
			return new MapLayerPair(new ConstantBiomeMapLayer($seed, BiomeIds::SKY), null);
		}

		return new MapLayerPair(null, null);
	}

	private Random $random;

	public function __construct(private int $seed)
	{
		$this->random = new Random();
	}

	public function setCoordsSeed(int $x, int $z): void
	{
		$this->random->setSeed($this->seed);
		$this->random->setSeed($x * $this->random->nextInt() + $z * $this->random->nextInt() ^ $this->seed);
	}

	public function nextInt(int $max): int
	{
		return $this->random->nextBoundedInt($max);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $size_x
	 * @param int $size_z
	 *
	 * @return int[]
	 */
	abstract public function generateValues(int $x, int $z, int $size_x, int $size_z): array;
}