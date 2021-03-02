<?php
declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\overworld\populator;

use muqsit\vanillagenerator\generator\Populator;
use muqsit\vanillagenerator\generator\utils\CaveRandom;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Math;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

class CavePopulator implements Populator
{
	// The density of vanilla caves. Higher = more caves, closer together.
	// Default: 14 (value used in vanilla)
	const CAVE_DENSITY = 14;
	// The maximum y-coordinate at which vanilla caves can generate.
	// Default: 128
	const CAVE_MAX_Y = 128;
	// The minimum y-coordinate at which vanilla caves can generate.
	// Default: 8
	const CAVE_MIN_Y = 8;
	// Default cave range
	const CAVE_RANGE = 8;
	// Lava (or water in water regions) spawns at and below this y-coordinate.
	// Default: 10
	const CAVE_LIQUID_ALTITUDE = 10;

	/** @var CaveRandom */
	private CaveRandom $random;

	public function populate(ChunkManager $world, Random $random, int $chunkX, int $chunkZ, Chunk $chunk): void
	{
		$this->random = new CaveRandom($random->getSeed());

		$allCondition = [];
		for ($x = 0; $x < 16; $x++) {
			for ($z = 0; $z < 16; $z++) {
				$allCondition[$x][$z] = true;
			}
		}

		$j = $this->random->nextLong();
		$k = $this->random->nextLong();

		$chunk = $world->getChunk($chunkX, $chunkZ);
		for ($currentChunkX = $chunkX - self::CAVE_RANGE; $currentChunkX <= $chunkX + self::CAVE_RANGE; $currentChunkX++) {
			for ($currentChunkZ = $chunkZ - self::CAVE_RANGE; $currentChunkZ <= $chunkZ + self::CAVE_RANGE; $currentChunkZ++) {
				$rx = $currentChunkX * $j;
				$rz = $currentChunkZ * $k;

				$this->random->setSeed($rx ^ $rz ^ $random->getSeed());

				$this->recursiveGenerate($currentChunkX, $currentChunkZ, $chunkX, $chunkZ, $chunk, true, $allCondition);
			}
		}
	}

	/**
	 * Calls addTunnel and addRoom (wrapper for addTunnel) for this chunk.
	 * Note that each call to this function (and subsequently addTunnel) will be done with the same rand seed.
	 * This means that when a chunk is checked multiple times by different neighbor chunks, each time it will be processed
	 * the same way, ensuring the tunnels are always consistent and connecting.
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param int $refChunkX
	 * @param int $refChunkZ
	 * @param Chunk $chunk
	 * @param bool $addRooms
	 * @param array $carvingMask
	 */
	protected function recursiveGenerate(int $chunkX, int $chunkZ, int $refChunkX, int $refChunkZ, Chunk $chunk, bool $addRooms = true, array $carvingMask = []): void
	{
		$numAttempts = $this->random->nextBoundedInt($this->random->nextBoundedInt($this->random->nextBoundedInt(15) + 1) + 1);

		if ($this->random->nextBoundedInt(100) > self::CAVE_DENSITY) {
			$numAttempts = 0;
		}

		for ($i = 0; $i < $numAttempts; ++$i) {
			$caveStartX = $chunkX * 16 + $this->random->nextBoundedInt(16);
			$caveStartY = $this->random->nextBoundedInt(self::CAVE_MAX_Y - self::CAVE_MIN_Y) + self::CAVE_MIN_Y;
			$caveStartZ = $chunkZ * 16 + $this->random->nextBoundedInt(16);

			$numAddTunnelCalls = 1;

			if ($addRooms && $this->random->nextBoundedInt(4) == 0) {
				$this->addRoom($this->random->nextLong(), $chunk, $refChunkX, $refChunkZ, $caveStartX, $caveStartY, $caveStartZ, $carvingMask);
				$numAddTunnelCalls += $this->random->nextBoundedInt(4);
			}

			for ($j = 0; $j < $numAddTunnelCalls; ++$j) {
				$yaw = $this->random->nextFloat() * ((float)M_PI * 2);
				$pitch = ($this->random->nextFloat() - 0.5) * 2.0 / 8.0;
				$width = $this->random->nextFloat() * 2.0 + $this->random->nextFloat();

				// Chance of wider caves.
				// Although not actually related to adding rooms, I perform an addRoom check here
				// to avoid the chance of really large caves when generating surface caves.
				if ($addRooms && $this->random->nextBoundedInt(10) == 0) {
					$width *= $this->random->nextFloat() * $this->random->nextFloat() * 3.0 + 1.0;
				}

				$this->addTunnel($this->random->nextLong(), $chunk, $refChunkX, $refChunkZ, $caveStartX, $caveStartY, $caveStartZ, $width, $yaw, $pitch, 0, 0, 1.0, $carvingMask);
			}
		}
	}

	private function addRoom(int $seed, Chunk $chunk, int $refChunkX, int $refChunkZ, float $caveStartX, float $caveStartY, float $caveStartZ, array $carvingMask = []): void
	{
		$this->addTunnel($seed, $chunk, $refChunkX, $refChunkZ, $caveStartX, $caveStartY, $caveStartZ, 1.0 + $this->random->nextFloat() * 6.0, 0.0, 0.0, -1, -1, 0.5, $carvingMask);
	}

	private function addTunnel(int $seed, Chunk $chunk, int $refChunkX, int $refChunkZ, float $caveStartX, float $caveStartY, float $caveStartZ, float $width, float $yaw, float $pitch, int $startCounter, int $endCounter, float $heightModifier, array $carvingMask = []): void
	{
		$random = new CaveRandom($seed);

		// Center block of the origin chunk
		$originBlockX = ($refChunkX * 16 + 8);
		$originBlockZ = ($refChunkZ * 16 + 8);

		// Variables to slightly change the yaw/pitch for each iteration in the while loop below.
		$yawModifier = 0.0;
		$pitchModifier = 0.0;

		// Raw calls to addTunnel from recursiveGenerate give startCounter and endCounter a value of 0.
		// Calls from addRoom make them both -1.

		// This appears to be called regardless of where addTunnel was called from.
		if ($endCounter <= 0) {
			$i = self::CAVE_RANGE * 16 - 16;
			$endCounter = $i - $random->nextBoundedInt((int)($i / 4));
		}

		// Whether or not this function call was made from addRoom.
		$comesFromRoom = false;

		// Only called if the function call came from addRoom.
		// If this call came from addRoom, startCounter is set to halfway to endCounter.
		// If this is a raw call from recursiveGenerate, startCounter will be zero.
		if ($startCounter == -1) {
			$startCounter = $endCounter / 2;
			$comesFromRoom = true;
		}

		$randomCounterValue = $random->nextBoundedInt((int)($endCounter / 2)) + $endCounter / 4;

		// Loops one block at a time to the endCounter (about 6-7 chunks away on average).
		// startCounter starts at either zero or endCounter / 2.
		while ($startCounter < $endCounter) {
			// Appears to change how wide caves are. Value will be between 1.5 and 1.5 + width.
			// Note that caves will become wider toward the middle, and close off on the ends.
			$xzOffset = 1.5 + (double)(sin((float)$startCounter * (float)M_PI / (float)$endCounter) * $width);

			$yOffset = $xzOffset * $heightModifier;

			$pitchXZ = cos($pitch);
			$pitchY = sin($pitch);

			$caveStartX += cos($yaw) * $pitchXZ;
			$caveStartY += $pitchY;
			$caveStartZ += sin($yaw) * $pitchXZ;

			$flag = $random->nextBoundedInt(6) == 0;
			if ($flag) {
				$pitch = $pitch * 0.92;
			} else {
				$pitch = $pitch * 0.7;
			}

			$pitch = $pitch + $pitchModifier * 0.1;
			$yaw += $yawModifier * 0.1;

			$pitchModifier = $pitchModifier * 0.9;
			$yawModifier = $yawModifier * 0.75;

			$pitchModifier = $pitchModifier + ($random->nextFloat() - $random->nextFloat()) * $random->nextFloat() * 2.0;
			$yawModifier = $yawModifier + ($random->nextFloat() - $random->nextFloat()) * $random->nextFloat() * 4.0;

			if ((!$comesFromRoom) && ($startCounter === $randomCounterValue) && ($width > 1.0) && ($endCounter > 0)) {
				$this->addTunnel($random->nextLong(), $chunk, $refChunkX, $refChunkZ, $caveStartX, $caveStartY, $caveStartZ, $random->nextFloat() * 0.5 + 0.5, $yaw - ((float)M_PI / 2), $pitch / 3.0, $startCounter, $endCounter, 1.0, $carvingMask);
				$this->addTunnel($random->nextLong(), $chunk, $refChunkX, $refChunkZ, $caveStartX, $caveStartY, $caveStartZ, $random->nextFloat() * 0.5 + 0.5, $yaw + ((float)M_PI / 2), $pitch / 3.0, $startCounter, $endCounter, 1.0, $carvingMask);

				return;
			}

			if ($comesFromRoom || $random->nextBoundedInt(4) != 0) {
				$caveStartXOffsetFromCenter = $caveStartX - $originBlockX; // Number of blocks from current caveStartX to center of origin chunk
				$caveStartZOffsetFromCenter = $caveStartZ - $originBlockZ; // Number of blocks from current caveStartZ to center of origin chunk
				$distanceToEnd = $endCounter - $startCounter;
				$d7 = $width + 2.0 + 16.0;

				// I think this prevents caves from generating too far from the origin chunk
				if ($caveStartXOffsetFromCenter * $caveStartXOffsetFromCenter + $caveStartZOffsetFromCenter * $caveStartZOffsetFromCenter - $distanceToEnd * $distanceToEnd > $d7 * $d7) {
					return;
				}


				// Only continue if cave start is close enough to origin
				if ($caveStartX >= $originBlockX - 16.0 - $xzOffset * 2.0 && $caveStartZ >= $originBlockZ - 16.0 - $xzOffset * 2.0 && $caveStartX <= $originBlockX + 16.0 + $xzOffset * 2.0 && $caveStartZ <= $originBlockZ + 16.0 + $xzOffset * 2.0) {
					$minX = Math::floorFloat($caveStartX - $xzOffset) - $refChunkX * 16 - 1;
					$minY = Math::floorFloat($caveStartY - $yOffset) - 1;
					$minZ = Math::floorFloat($caveStartZ - $xzOffset) - $refChunkZ * 16 - 1;
					$maxX = Math::floorFloat($caveStartX + $xzOffset) - $refChunkX * 16 + 1;
					$maxY = Math::floorFloat($caveStartY + $yOffset) + 1;
					$maxZ = Math::floorFloat($caveStartZ + $xzOffset) - $refChunkZ * 16 + 1;

					if ($minX < 0) $minX = 0;
					if ($maxX > 16) $maxX = 16;
					if ($minY < 1) $minY = 1;
					if ($maxY > 248) $maxY = 248;
					if ($minZ < 0) $minZ = 0;
					if ($maxZ > 16) $maxZ = 16;

					for ($currX = $minX; $currX < $maxX; ++$currX) {
						// Distance along the x-axis from the center (caveStart) of this ellipsoid.
						// You can think of this value as (x/a), where a is the length of the ellipsoid's semi-axis in the x direction.
						$xAxisDist = ((double)($currX + $refChunkX * 16) + 0.5 - $caveStartX) / $xzOffset;

						for ($currZ = $minZ; $currZ < $maxZ; ++$currZ) {
							// Distance along the z-axis from the center (caveStart) of this ellipsoid.
							// You can think of this value as (z/b), where b is the length of the ellipsoid's semi-axis in the z direction (same as a in this case).
							$zAxisDist = ((double)($currZ + $refChunkZ * 16) + 0.5 - $caveStartZ) / $xzOffset;

							// Skip column if carving mask not set
							if (!$carvingMask[$currX][$currZ]) continue;

							// Only operate on points within ellipse on XZ axis. Avoids unnecessary computation along y axis
							if ($xAxisDist * $xAxisDist + $zAxisDist * $zAxisDist < 1.0) {
								for ($currY = $maxY; $currY > $minY; --$currY) {
									// Distance along the y-axis from the center (caveStart) of this ellipsoid.
									// You can think of this value as (y/c), where c is the length of the ellipsoid's semi-axis in the y direction.
									$yAxisDist = ((double)($currY - 1) + 0.5 - $caveStartY) / $yOffset;

									// Only operate on points within the ellipsoid.
									// This conditional is validating the current coordinate against the equation of the ellipsoid, that is,
									// (x/a)^2 + (z/b)^2 + (y/c)^2 <= 1.
									if ($yAxisDist > -0.7 && $xAxisDist * $xAxisDist + $yAxisDist * $yAxisDist + $zAxisDist * $zAxisDist < 1.0) {
										$this->digBlock($chunk, $currX, $currY, $currZ, self::CAVE_LIQUID_ALTITUDE);
									}
								}
							}
						}
					}
					if ($comesFromRoom) {
						break;
					}
				}
			}
			$startCounter++;
		}
	}

	private function digBlock(Chunk $chunk, int $currX, int $currY, int $currZ, int $caveLiquidAltitude): void
	{
		$block = BlockFactory::getInstance()->fromFullBlock($chunk->getFullBlock($currX, $currY, $currZ));
		$blockAbove = BlockFactory::getInstance()->fromFullBlock($chunk->getFullBlock($currX, $currY + 1, $currZ));

		if (self::canReplaceBlock($block, $blockAbove)) {
			if ($currY - 1 < $caveLiquidAltitude) {
				$chunk->setFullBlock($currX, $currY, $currZ, VanillaBlocks::LAVA()->getFullId());
			} else {
				$chunk->setFullBlock($currX, $currY, $currZ, VanillaBlocks::AIR()->getFullId());
			}
		}
	}

	/**
	 * Determines if the Block is suitable to be replaced during cave generation.
	 * Basically returns true for most common worldgen blocks (e.g. stone, dirt, sand), false if the block is air.
	 *
	 * @param Block $block the block's IBlockState
	 * @param Block $blockAbove the IBlockState of the block above this one
	 * @return bool Returns true if the blockState can be replaced
	 */
	public static function canReplaceBlock(Block $block, Block $blockAbove): bool
	{
		// Avoid damaging trees
		if (in_array($block->getId(), [BlockLegacyIds::LEAVES, BlockLegacyIds::LEAVES2, BlockLegacyIds::LOG, BlockLegacyIds::LOG2])) {
			return false;
		}

		// Avoid digging out under trees
		if (in_array($blockAbove->getId(), [BlockLegacyIds::LOG, BlockLegacyIds::LOG2])) {
			return false;
		}

		// Mine-able blocks
		if (in_array($block->getId(), [
			BlockLegacyIds::STONE,
			BlockLegacyIds::DIRT,
			BlockLegacyIds::GRASS,
			BlockLegacyIds::HARDENED_CLAY,
			BlockLegacyIds::STAINED_HARDENED_CLAY,
			BlockLegacyIds::SANDSTONE,
			BlockLegacyIds::RED_SANDSTONE,
			BlockLegacyIds::MYCELIUM,
			BlockLegacyIds::SNOW_LAYER])) {
			return true;
		}

		// Only accept gravel and sand if water is not directly above it
		return ($block->getId() === BlockLegacyIds::SAND || $block->getId() === BlockLegacyIds::GRAVEL)
			&& !($blockAbove instanceof Liquid);
	}
}