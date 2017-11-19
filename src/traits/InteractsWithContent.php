<?php

namespace Missionx\Mediable\Manipulation\traits;

use Missionx\Mediable\Manipulation\Helpers\MediaMover;

trait InteractsWithContent{

	/**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
    	parent::__construct( $attributes );
    	$this->imageManipulationProperties = collect();
	}

	/**
     * Get the path to the file relative to the root of the disk.
     * @return string
     */
	public function getDiskPath(){
		$basename = $this->isImage() ? $this->getManipulatedImageBasename() : $this->basename;

		return ltrim(rtrim($this->directory, '/').'/'.ltrim($basename, '/'), '/');
	}

    /**
     * Move the file to a new location on disk.
     *
     * Will invoke the `save()` method on the model after the associated file has been moved to prevent synchronization errors
     * @param  string $destination directory relative to disk root
     * @param  string $filename    filename. Do not include extension
     * @return void
     */
    public function move($destination, $filename = null)
    {
        //Manipulation Properties old values
        if( $this->isImage() ){
            $oldValues = $this->imageManipulationProperties->all();
            $this->clearImageManipulationProperties();
        }

        $mediaMover = app( MediaMover::class )
                        ->media( $this )
                        ->toDirectory( $destination )
                        ->withFilename( $filename );

        $mediaMover->move();

        $this->moveManipulationResults( $mediaMover );

        $mediaMover->updateDatabase();

        //preserve old state
        if( $this->isImage() ){
            $this->setImageManipulationProperties( $oldValues );
        }
    }

}