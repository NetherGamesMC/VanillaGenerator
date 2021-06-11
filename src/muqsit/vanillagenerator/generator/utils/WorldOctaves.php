<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\utils;

use PerlinOctaveGenerator;
use SimplexOctaveGenerator;

class WorldOctaves
{

	public function __construct(
		public SimplexOctaveGenerator|PerlinOctaveGenerator $height,
		public SimplexOctaveGenerator|PerlinOctaveGenerator $roughness,
		public SimplexOctaveGenerator|PerlinOctaveGenerator $roughness_2,
		public SimplexOctaveGenerator|PerlinOctaveGenerator $detail,
		public SimplexOctaveGenerator|PerlinOctaveGenerator $surface
	)
	{
	}
}