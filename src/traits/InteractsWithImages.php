<?php

namespace Missionx\Mediable\Manipulation\traits;

use Intervention\Image\Facades\Image;

trait InteractsWithImages{

	/**
	 * image manipulation attributes and args
	 * methods of InterventionImage
	 * @var Illuminate\Support\Collection
	 */
	public $imageManipulationProperties;

	/**
	 * Whether you want to enable or disable manipulation
	 * @var boolean
	 */
	private $enableImageManipulation = true;

	/**
     * Get the absolute filesystem path to the file.
     * @return string
     */
    public function getImagePlainAbsolutePath()
    {
    	//preserve state
    	$oldState = $this->enableImageManipulation;
    	$this->disableImageManipulation();

        $path = $this->getUrlGenerator()->getAbsolutePath();

        //preserveOldState
        $this->enableImageManipulation = $oldState;

        return $path;
    }

	/**
	 * check if Media Can Be Manipualted
	 * if not an image then it can't be manipulated
	 * if image manipulation is disabled then can't be manipulated
	 * if image manipulated Properties were empty then it can't be manipulated
	 * if original file doesn't exist then it can be manipulated -> Intervention\Image will throw error
	 * @return boolean
	 */
	public function canImageBeManipulated(){
		return  $this->isImageManipulationEnabled() &&
				!$this->imageManipulationProperties->isEmpty() &&
				file_exists( $this->getImagePlainAbsolutePath() );
	}

	/**
	 * check if current media is image
	 * @return boolean
	 */
	public function isImage(){
		return $this->aggregate_type == static::TYPE_IMAGE;
	}

	/**
	 * Set Image Manipulation Properties
	 * @param array $properties
	 * @return  $this
	 */
	public function setImageManipulationProperties( array $properties ){
		$this->imageManipulationProperties = $this->imageManipulationProperties->merge( $properties );
		return $this;
	}

	/**
	 * get $imageManipulationProperties
	 * @return Illuminate\Support\Collection
	 */
	public function getImageManipulationProperties(){
		return $this->imageManipulationProperties;
	}

	public function clearImageManipulationProperties(){
		$this->imageManipulationProperties = collect([]);
		return $this;
	}

	/*----------------------------------------
	 * Image Manipulation Logic
	 ---------------------------------------*/

	/**
	 * apply Manipulation on image
	 * @param  string $method Intervention\Image method used to manipulate image with
	 * @param  array  $args   Intervention\Image method args
	 * @return $this
	 */
	public function manipulateImage( $method, $args = [] ){
		$this->imageManipulationProperties->put($method ,$args);
		return $this;
	}

	/**
	 * generates media name according to manipulation args
	 * @return string
	 */
	public function getManipulatedImageBasename(){
		if( !$this->isImageManipulationEnabled() || $this->imageManipulationProperties->isEmpty() ){
			return $this->basename;
		}

		return "{$this->filename}-{$this->convertPropertiesToString( $this->imageManipulationProperties )}.{$this->extension}";
	}

	/**
	 * apply image manipulation
	 * @return $this
	 */
	public function applyImageManipulation(){
		// if we already manipulate the image then return
		if( !$this->isImage() || !$this->canImageBeManipulated() || file_exists( $absolutePath = $this->getAbsolutePath() ) ){
			return $this;
		}

		$image = Image::make( $this->getImagePlainAbsolutePath() );
		$this->imageManipulationProperties->each(function($args, $method) use( &$image ){
			$image->{$method}(...array_values($args));
		});
		$image->save( $absolutePath );

		$this->logManipulationProperties( $this->imageManipulationProperties->all() );

		return $this;
	}

	/*----------------------------------------
	 * Image Manipulation Enable|disable
	 ---------------------------------------*/
	/**
	 * Enable Image Manipulation Logic
	 * @return $this
	 */
	public function enableImageManipulation(){
		$this->enableImageManipulation = true;
		return $this;
	}

	/**
	 * Disable Image Manipulation
	 * @return $this
	 */
	public function disableImageManipulation(){
		$this->enableImageManipulation = false;
		return $this;
	}

	/**
	 * check of image manipulation is enabled
	 * @return boolean
	 */
	public function isImageManipulationEnabled(){
		return $this->enableImageManipulation;
	}

	/*----------------------------------------
	 * Register Image Manipulations
	 ---------------------------------------*/

	/**
	 * register image manipulations properties to be
	 * @return array array of arrays
	 */
	public function registerImageManipulations(){
		return [];
	}

	/**
	 * bootTrait to generate registered Image Manipulations
	 */
	public static function bootInteractsWithImages(){
		static::created(function($model){

			collect((array) $model->registerImageManipulations())->each(function($imageManipulationProperties){
				$model->setImageManipulationProperties( $imageManipulationProperties );

				$model->applyImageManipulation();

				$model->clearImageManipulationProperties();
			});

		});
	}


}
