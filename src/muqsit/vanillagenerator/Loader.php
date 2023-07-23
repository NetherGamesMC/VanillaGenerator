<?php

declare(strict_types = 1);

namespace muqsit\vanillagenerator;

use muqsit\vanillagenerator\generator\OverworldGenerator;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use ReflectionException;
use ReflectionMethod;

final class Loader extends PluginBase {

	private const EXT_MCGENERATOR_VERSION = "2.1.1";

	public function onLoad(): void{
		if(!extension_loaded('vanillagenerator')){
			$this->getLogger()->critical("Unable to find the vanillagenerator extension.");
			$this->getServer()->getPluginManager()->disablePlugin($this);

			return;
		}elseif(($phpver = phpversion('vanillagenerator')) < self::EXT_MCGENERATOR_VERSION){
			$this->getLogger()->critical("vanillagenerator extension version " . self::EXT_MCGENERATOR_VERSION . " is required, you have $phpver.");
			$this->getServer()->getPluginManager()->disablePlugin($this);

			return;
		}

		$this->registerBlocks();

		$generatorManager = GeneratorManager::getInstance();
		$generatorManager->addGenerator(OverworldGenerator::class, "vanilla_overworld", fn() => null);
	}

	private function registerBlocks(): void{
		$blocks = VanillaBlocks::getAll();

		foreach($blocks as $blockSelection){
			foreach($blockSelection->generateStatePermutations() as $block){
				// "state" is a fancy word for "meta"
				$r = new ReflectionMethod(Block::class, 'encodeFullState');
				$r->setAccessible(true);

				try{
					$blockMeta = $r->invoke($block);

					// 1st bit = solid state
					// 2nd bit = transparent state
					// 3rd bit = flowable state
					// 4th bit = liquid state
					// 5th bit = light level (require 4 bits, 0-15)

					$value = 0;
					if($block->isSolid()) 			$value |= (1 << 0);
					if($block->isTransparent()) 	$value |= (1 << 1);
					if($block->canBeFlowedInto()) 	$value |= (1 << 2);
					if($block instanceof Liquid) 	$value |= (1 << 3);

					$value |= ($block->getLightLevel() << 4);

					\OverworldGenerator::registerBlock($block->getIdInfo()->getBlockTypeId(), $blockMeta, $value);
				}catch(ReflectionException){
				}
			}
		}
	}
}
