<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator;

use muqsit\vanillagenerator\generator\biomegrid\MapLayer;
use muqsit\vanillagenerator\generator\biomegrid\utils\MapLayerPair;
use muqsit\vanillagenerator\generator\overworld\WorldType;
use muqsit\vanillagenerator\generator\utils\WorldOctaves;
use OverworldChunkPopulator;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\World;
use Random;
use ReflectionException;
use ReflectionObject;

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

		OverworldChunkPopulator::init();
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $size_x
	 * @param int $size_z
	 *
	 * @return int[]
	 */
	public function getBiomeGridAtLowerRes(int $x, int $z, int $size_x, int $size_z): array
	{
		return $this->biome_grid->low_resolution->generateValues($x, $z, $size_x, $size_z);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $size_x
	 * @param int $size_z
	 *
	 * @return int[]
	 */
	public function getBiomeGrid(int $x, int $z, int $size_x, int $size_z): array
	{
		return $this->biome_grid->high_resolution->generateValues($x, $z, $size_x, $size_z);
	}

	protected function addPopulators(Populator ...$populators): void
	{
		array_push($this->populators, ...$populators);
	}

	/**
	 * @return WorldOctaves
	 */
	abstract protected function createWorldOctaves(): WorldOctaves;

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
	{
		$biomes = new VanillaBiomeGrid();
		$biome_values = $this->biome_grid->high_resolution->generateValues($chunkX * 16, $chunkZ * 16, 16, 16);
		for ($i = 0, $biome_values_c = count($biome_values); $i < $biome_values_c; ++$i) {
			$biomes->biomes[$i] = $biome_values[$i];
		}

		$this->generateChunkData($world, $chunkX, $chunkZ, $biomes);
	}

	abstract protected function generateChunkData(ChunkManager $world, int $chunk_x, int $chunk_z, VanillaBiomeGrid $biomes): void;

	/**
	 * @return WorldOctaves
	 */
	final protected function getWorldOctaves(): WorldOctaves
	{
		return $this->octave_cache ??= $this->createWorldOctaves();
	}

	/**
	 * @return Populator[]
	 */
	public function getDefaultPopulators(): array
	{
		return $this->populators;
	}

	/**
	 * @throws ReflectionException
	 */
	public function populateChunk(ChunkManager $world, int $chunk_x, int $chunk_z): void
	{
		/** @var Chunk $chunk */
		$chunk = $world->getChunk($chunk_x, $chunk_z);

//		$start = microtime(true);
//
//		foreach ($this->populators as $populator) {
//			$populator->populate($world, $this->random, $chunk_x, $chunk_z, $chunk);
//		}

		$start = microtime(true);

		$r = new ReflectionObject($world);
		$p = $r->getProperty('chunks');
		$p->setAccessible(true);

		$pelletedEntries = [];

		/**
		 * @var int $hash
		 * @var Chunk $chunkVal
		 */
		foreach ($p->getValue($world) as $hash => $chunkVal) {
			$array = array_fill(0, 16, null);

			foreach ($chunkVal->getSubChunks() as $y => $subChunk) {
				if (!$subChunk->isEmptyFast()) {
					$array[$y] = $subChunk->getBlockLayers()[0];
				} else {
					$newSubChunk = new SubChunk($subChunk->getEmptyBlockId(), [new PalettedBlockArray($subChunk->getEmptyBlockId())], $subChunk->getBlockSkyLightArray(), $subChunk->getBlockLightArray());
					$chunkVal->setSubChunk($y, $newSubChunk);

					$array[$y] = $newSubChunk->getBlockLayers()[0];
				}
			}

			$pelletedEntries[$hash] = $array;
		}

		OverworldChunkPopulator::populateChunk($pelletedEntries, World::chunkHash($chunk_x, $chunk_z), $this->random, $chunk->getBiomeIdArray());

		$end = microtime(true);

		print "Took " . round(($end - $start) * 1000, 3) . "ms to execute" . PHP_EOL;
	}

	public function getMaxY(): int
	{
		return World::Y_MAX;
	}
}