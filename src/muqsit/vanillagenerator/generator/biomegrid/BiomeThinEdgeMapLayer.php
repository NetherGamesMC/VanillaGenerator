<?php

declare(strict_types=1);

namespace muqsit\vanillagenerator\generator\biomegrid;

class BiomeThinEdgeMapLayer extends MapLayer{

	public function __construct(int $seed, private MapLayer $below_layer){
		parent::__construct($seed);
	}

	public function generateValues(int $x, int $z, int $size_x, int $size_z) : array{
		$grid_x = $x - 1;
		$grid_z = $z - 1;
		$grid_size_x = $size_x + 2;
		$grid_size_z = $size_z + 2;
		$values = $this->below_layer->generateValues($grid_x, $grid_z, $grid_size_x, $grid_size_z);

		$final_values = [];
		for($i = 0; $i < $size_z; ++$i){
			for($j = 0; $j < $size_x; ++$j){
				// This applies biome thin edges using Von Neumann neighborhood
				$center_val = $values[$j + 1 + ($i + 1) * $grid_size_x];
				$val = $center_val;

				$final_values[$j + $i * $size_x] = $val;
			}
		}

		return $final_values;
	}
}