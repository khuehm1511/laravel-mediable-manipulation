<?php

use Plank\Mediable\Media as MediableMedia;
use Missionx\Mediable\Manipulation\traits\InteractsWithImages;
use Missionx\Mediable\Manipulation\traits\InteractsWithContent;
use Missionx\Mediable\Manipulation\traits\InteractsWithManipulationProperties;

class Media extends MediableMedia
{
	use InteractsWithContent,
		InteractsWithImages,
		InteractsWithManipulationProperties;
}
