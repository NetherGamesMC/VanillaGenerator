<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator;

use muqsit\vanillagenerator\generator\nether\NetherGenerator;
use muqsit\vanillagenerator\generator\overworld\OverworldGenerator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Loader extends PluginBase implements Listener {

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

	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onMove(PlayerMoveEvent $event): void
	{
		$pos = $event->getPlayer()->getPosition();
		$event->getPlayer()->sendTip("Biome ID: " . (string)$event->getPlayer()->getWorld()->getBiomeId($pos->getFloorX(), $pos->getFloorZ()));
	}
}