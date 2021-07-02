<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator;

use muqsit\vanillagenerator\generator\biomegrid\MapLayer;
use muqsit\vanillagenerator\generator\biomegrid\utils\MapLayerPair;
use muqsit\vanillagenerator\generator\overworld\WorldType;
use muqsit\vanillagenerator\generator\utils\WorldOctaves;
use OverworldGenerator;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\BiomeArray;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\World;
use Random;
use ReflectionException;

abstract class VanillaGenerator extends Generator
{

	private ?WorldOctaves $octave_cache = null;

	/** @var Populator[] */
	private array $populators = [];

	private MapLayerPair $biome_grid;

	/** @var Random $random */
	protected $random;

	/** @var OverworldGenerator */
	private OverworldGenerator $generator;

	public function __construct(int $seed, int $environment, ?string $world_type = null, string $preset = "")
	{
		parent::__construct($seed, $preset);
		$this->random = new Random($seed);
		$this->biome_grid = MapLayer::initialize($seed, $environment, $world_type ?? WorldType::NORMAL);

		$this->generator = new OverworldGenerator($seed);
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
		$startMem = memory_get_usage(true);
		$start = microtime(true);
		$chunk = $world->getChunk($chunkX, $chunkZ);

		$biomeData = $chunk->getBiomeIdArray();
		$pelletedEntries = [];

		foreach ($chunk->getSubChunks() as $y => $subChunk) {
			if (!$subChunk->isEmptyFast()) {
				$pelletedEntries[$y] = $subChunk->getBlockLayers()[0];
			} else {
				$newSubChunk = new SubChunk($subChunk->getEmptyBlockId(), [new PalettedBlockArray($subChunk->getEmptyBlockId())], $subChunk->getBlockSkyLightArray(), $subChunk->getBlockLightArray());
				$chunk->setSubChunk($y, $newSubChunk);

				$pelletedEntries[$y] = $newSubChunk->getBlockLayers()[0];
			}
		}

		$biomes = $this->generator->generateChunk($pelletedEntries, $biomeData, World::chunkHash($chunkX, $chunkZ));

		(function () use ($biomes): void {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->biomeIds = new BiomeArray($biomes);
		})->call($chunk);

		$end = microtime(true);
		$endMem = memory_get_usage(true);

		print "Terrain population " . round(($end - $start) * 1000, 2) . 'ms ' . "$startMem $endMem \r\n";
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
//		$start = microtime(true);

//		foreach ($this->populators as $populator) {
//			$populator->populate($world, $this->random, $chunk_x, $chunk_z, $chunk);
//		}
//
//		$start = microtime(true);
//
//		$r = new ReflectionObject($world);
//		$p = $r->getProperty('chunks');
//		$p->setAccessible(true);
//
//		$biomeEntries = [];
//		$pelletedEntries = [];
//		$dirtyEntries = [];
//
//		/**
//		 * @var int $hash
//		 * @var Chunk $chunkVal
//		 */
//		foreach ($p->getValue($world) as $hash => $chunkVal) {
//			$array = [];
//
//			foreach ($chunkVal->getSubChunks() as $y => $subChunk) {
//				if (!$subChunk->isEmptyFast()) {
//					$array[$y] = $subChunk->getBlockLayers()[0];
//				} else {
//					$newSubChunk = new SubChunk($subChunk->getEmptyBlockId(), [new PalettedBlockArray($subChunk->getEmptyBlockId())], $subChunk->getBlockSkyLightArray(), $subChunk->getBlockLightArray());
//					$chunkVal->setSubChunk($y, $newSubChunk);
//
//					$array[$y] = $newSubChunk->getBlockLayers()[0];
//				}
//			}
//
//			$pelletedEntries[$hash] = $array;
//			$biomeEntries[$hash] = $chunkVal->getBiomeIdArray();
//			$dirtyEntries[$hash] = $chunkVal->isDirty();
//		}
//
//		$this->generator->populateChunk($pelletedEntries, $biomeEntries, $dirtyEntries, World::chunkHash($chunk_x, $chunk_z));
//
//		foreach ($dirtyEntries as $hash => $dirtyEntry) {
//			World::getXZ($hash, $x, $z);
//
//			if ($dirtyEntry) {
//				$world->getChunk($x, $z)->setDirty();
//			}
//		}
//
//		$end = microtime(true);
//
//		print "Took " . round(($end - $start) * 1000, 3) . "ms to execute" . PHP_EOL;
	}

	public function getMaxY(): int
	{
		return World::Y_MAX;
	}
}