<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator;

use muqsit\vanillagenerator\generator\biomegrid\MapLayer;
use muqsit\vanillagenerator\generator\biomegrid\utils\MapLayerPair;
use muqsit\vanillagenerator\generator\overworld\WorldType;
use muqsit\vanillagenerator\generator\utils\WorldOctaves;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\World;
use Random;
use RuntimeException;

abstract class VanillaGenerator extends Generator
{

	private ?WorldOctaves $octave_cache = null;

	/** @var Populator[] */
	private array $populators = [];

	private MapLayerPair $biome_grid;

	/** @var Random $random */
	protected $random;

	public function __construct(int $seed, int $environment, ?string $world_type = null, string $preset = "")
	{
		parent::__construct($seed, $preset);
		$this->random = new Random($seed);
		$this->biome_grid = MapLayer::initialize($seed, $environment, $world_type ?? WorldType::NORMAL);
	}

	protected function addPopulators(Populator ...$populators): void
	{
		array_push($this->populators, ...$populators);
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
	{
		$biomes = new VanillaBiomeGrid();
		$biome_values = $this->biome_grid->high_resolution->generateValues($chunkX * 16, $chunkZ * 16, 16, 16);
		for ($i = 0, $biome_values_c = count($biome_values); $i < $biome_values_c; ++$i) {
			$biomes->biomes[$i] = $biome_values[$i];
		}

		$this->generateChunkData($world, $chunkX, $chunkZ, $biomes);
	}

	abstract protected function createWorldOctaves(): ?WorldOctaves;

	abstract protected function generateChunkData(ChunkManager $world, int $chunk_x, int $chunk_z, VanillaBiomeGrid $biomes): void;

	/**
	 * @return WorldOctaves
	 */
	final protected function getWorldOctaves(): WorldOctaves
	{
		return $this->octave_cache ??= $this->createWorldOctaves() ?? throw new RuntimeException("OverworldGenerator cannot use internal world octaves.");
	}

	/**
	 * @return Populator[]
	 */
	public function getDefaultPopulators(): array
	{
		return $this->populators;
	}

	public function populateChunk(ChunkManager $world, int $chunk_x, int $chunk_z): void
	{
		/** @var Chunk $chunk */
		$chunk = $world->getChunk($chunk_x, $chunk_z);
		foreach ($this->populators as $populator) {
			$populator->populate($world, $this->random, $chunk_x, $chunk_z, $chunk);
		}
	}

	public function getMaxY(): int
	{
		return World::Y_MAX;
	}
}