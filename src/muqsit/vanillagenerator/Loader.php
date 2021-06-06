<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator;

use muqsit\vanillagenerator\generator\nether\NetherGenerator;
use muqsit\vanillagenerator\generator\overworld\OverworldGenerator;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Loader extends PluginBase{

	public function onLoad() : void{
		if(!extension_loaded('extnoise')){
			$this->getLogger()->critical('Unable to find the NetherGames Noise Libraries (extnoise) extension. Generator features disabled');
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}

		$generatorManager = GeneratorManager::getInstance();
		$generatorManager->addGenerator(NetherGenerator::class, "vanilla_nether");
		$generatorManager->addGenerator(OverworldGenerator::class, "vanilla_overworld");
	}
}