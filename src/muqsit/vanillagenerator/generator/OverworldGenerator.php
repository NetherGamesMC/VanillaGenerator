<?php

declare(strict_types = 1);

namespace muqsit\vanillagenerator\generator;

use InvalidArgumentException;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\BiomeArray;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\World;
use ReflectionException;
use ReflectionObject;

class OverworldGenerator extends Generator {
	/** @var \OverworldGenerator */
	private \OverworldGenerator $generator;

	public function __construct(int $seed, string $preset){
		parent::__construct($seed, $preset);

		$enableUHC = false;

		$presets = explode(':', $preset);
		foreach($presets as $preset){
			if(empty($preset)) continue;

			$settings = explode(',', $preset);
			if(count($settings) < 2){
				throw new InvalidArgumentException("World preset must have a key and a value respectively");
			}

			switch($settings[0]){
				case "isUHC":
					$enableUHC = (int)$settings[1] === 1;
					break;
				case "environment":
				case "amplification":
					// TODO: These presets are available in mc-generator but they remain inaccessible for now.
			}
		}

		$this->generator = new \OverworldGenerator($seed, $enableUHC);
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void{
		$chunk = $world->getChunk($chunkX, $chunkZ);

		$palettedChunksArray = [];
		$palettedBiomesArray = [];
		foreach($chunk->getSubChunks() as $y => $subChunk){
			if(!$subChunk->isEmptyFast()){
				$palettedChunksArray[$y] = $subChunk->getBlockLayers()[0];
				$palettedBiomesArray[$y] = $subChunk->getBiomeArray();
			}else{
				$newSubChunk = new SubChunk($subChunk->getEmptyBlockId(), [new PalettedBlockArray($subChunk->getEmptyBlockId())], new PalettedBlockArray(BiomeIds::OCEAN), $subChunk->getBlockSkyLightArray(), $subChunk->getBlockLightArray());
				$chunk->setSubChunk($y, $newSubChunk);

				$palettedChunksArray[$y] = $newSubChunk->getBlockLayers()[0];
				$palettedBiomesArray[$y] = $newSubChunk->getBiomeArray();
			}
		}

		$this->generator->generateChunk($palettedChunksArray, $palettedBiomesArray, World::chunkHash($chunkX, $chunkZ));
	}

	/**
	 * @throws ReflectionException
	 */
	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void{
		$r = new ReflectionObject($world);
		$p = $r->getProperty('chunks');
		$p->setAccessible(true);

		$blockEntries = [];

		/**
		 * @var int   $hash
		 * @var Chunk $chunkVal
		 */
		foreach($p->getValue($world) as $hash => $chunkVal){
			$array = [];

			foreach($chunkVal->getSubChunks() as $y => $subChunk){
				if(!$subChunk->isEmptyFast()){
					$array[0][$y - Chunk::MIN_SUBCHUNK_INDEX] = $subChunk->getBlockLayers()[0];
					$array[1][$y - Chunk::MIN_SUBCHUNK_INDEX] = $subChunk->getBiomeArray();
				}else{
					$newSubChunk = new SubChunk($subChunk->getEmptyBlockId(), [new PalettedBlockArray($subChunk->getEmptyBlockId())], new PalettedBlockArray(BiomeIds::OCEAN), $subChunk->getBlockSkyLightArray(), $subChunk->getBlockLightArray());
					$chunkVal->setSubChunk($y, $newSubChunk);

					$array[0][$y - Chunk::MIN_SUBCHUNK_INDEX] = $newSubChunk->getBlockLayers()[0];
					$array[1][$y - Chunk::MIN_SUBCHUNK_INDEX] = $newSubChunk->getBiomeArray();
				}
			}

			$blockEntries[] = [$hash, $array, $chunkVal->isTerrainDirty()];
		}

		$this->generator->populateChunk($blockEntries, World::chunkHash($chunkX, $chunkZ));

		foreach($blockEntries as [$hash, $array, $dirtyEntry]){
			World::getXZ($hash, $x, $z);

			$c = $world->getChunk($x, $z);

			if($dirtyEntry){
				$c->setTerrainDirtyFlag(Chunk::DIRTY_FLAG_BLOCKS, true);
				$c->setTerrainDirtyFlag(Chunk::DIRTY_FLAG_BIOMES, true);
			}
		}
	}
}