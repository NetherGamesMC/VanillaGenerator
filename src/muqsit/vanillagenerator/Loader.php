<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator;

use muqsit\vanillagenerator\generator\nether\NetherGenerator;
use muqsit\vanillagenerator\generator\overworld\OverworldGenerator;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Loader extends PluginBase{

	private const EXT_NOISE_VERSION = "1.3.0";

	public function onLoad() : void{
		if(!extension_loaded('extnoise')){
			$this->getLogger()->critical("Unable to find the extnoise extension.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}elseif(($phpver = phpversion('extnoise')) === self::EXT_NOISE_VERSION){
			$this->getLogger()->critical("Version " . self::EXT_NOISE_VERSION . " is required, you have $phpver.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}

		$generatorManager = GeneratorManager::getInstance();
		$generatorManager->addGenerator(NetherGenerator::class, "vanilla_nether");
		$generatorManager->addGenerator(OverworldGenerator::class, "vanilla_overworld");
	}
}