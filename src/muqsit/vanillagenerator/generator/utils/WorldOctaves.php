<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\utils;

use PerlinOctaveGenerator;
use SimplexOctaveGenerator;

class WorldOctaves{

	public SimplexOctaveGenerator|PerlinOctaveGenerator $height;

	public SimplexOctaveGenerator|PerlinOctaveGenerator $roughness;

	public SimplexOctaveGenerator|PerlinOctaveGenerator $roughness_2;

	public SimplexOctaveGenerator|PerlinOctaveGenerator $detail;

	public SimplexOctaveGenerator|PerlinOctaveGenerator $surface;

	public function __construct(
		SimplexOctaveGenerator|PerlinOctaveGenerator $height,
		SimplexOctaveGenerator|PerlinOctaveGenerator $roughness,
		SimplexOctaveGenerator|PerlinOctaveGenerator $roughness_2,
		SimplexOctaveGenerator|PerlinOctaveGenerator $detail,
		SimplexOctaveGenerator|PerlinOctaveGenerator $surface
	){
		$this->height = $height;
		$this->roughness = $roughness;
		$this->roughness_2 = $roughness_2;
		$this->detail = $detail;
		$this->surface = $surface;
	}
}