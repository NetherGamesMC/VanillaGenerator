<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\utils;

use PerlinOctaveGenerator;
use SimplexOctaveGenerator;

class NetherWorldOctaves extends WorldOctaves{

	public SimplexOctaveGenerator|PerlinOctaveGenerator $soul_sand;

	public SimplexOctaveGenerator|PerlinOctaveGenerator $gravel;

	public function __construct(
		SimplexOctaveGenerator|PerlinOctaveGenerator $height,
		SimplexOctaveGenerator|PerlinOctaveGenerator $roughness,
		SimplexOctaveGenerator|PerlinOctaveGenerator $roughness_2,
		SimplexOctaveGenerator|PerlinOctaveGenerator $detail,
		SimplexOctaveGenerator|PerlinOctaveGenerator $surface,
		SimplexOctaveGenerator|PerlinOctaveGenerator $soul_sand,
		SimplexOctaveGenerator|PerlinOctaveGenerator $gravel
	){
		parent::__construct($height, $roughness, $roughness_2, $detail, $surface);
		$this->soul_sand = $soul_sand;
		$this->gravel = $gravel;
	}
}