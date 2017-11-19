<?php

namespace Missionx\Mediable\Manipulation\traits;

/**
 * Laravel Mediable has two function to update original files which are move and rename
 * both functions will affect original files but won't affect saved manipulation resulte files
 * there are two ways to reflect the move|rename effects on manipulation resulte files
 * first one is to use File::glob({patternHere}) to find all related files then move|rename them
 * the other way is to save a json file with the manipulation methods
 * the second way is better because it will provide a log for our manipulations on file and we don't need to Find::glob() in all files
 */
trait InteractsWithManipulationProperties{

	/**
	 * manipulations log file path relative to disk root
	 * @return string
	 */
	public function manipulationLogsDiskPath(){

		//save old state
		$oldState = $this->enableImageManipulation;
		$this->disableImageManipulation();

		$manipulationsLogsPath = $this->getDiskPath().'.json';

		$this->enableImageManipulation = $oldState;

		return $manipulationsLogsPath;
	}

	/**
	 * get manipulations logs file content
	 * @return array
	 */
	public function manipulationsLogs(){
		if( !$this->storage()->exists( $this->manipulationLogsDiskPath() ) ){
			return [];
		}

		return json_decode( $this->storage()->get( $this->manipulationLogsDiskPath() ), true );
	}

	/**
	 * save manipulation properties in manipulations logs file
	 * @param  array $properties
	 * @return $this
	 */
	public function logManipulationProperties( array $properties ){
		$logs = $this->manipulationsLogs();

		$logs[] = $properties;

		$this->storage()->put( $this->manipulationLogsDiskPath(), json_encode( $logs ) );
		return $this;
	}

	/**
	 * reflect move effect on manipulation files
	 * @return $this
	 */
	public function moveManipulationResults( $mediaMover ){
		collect( $this->manipulationsLogs() )->each(function($manipulationProperties) use( $mediaMover ) {
			$this->clearImageManipulationProperties();
			$this->setImageManipulationProperties( $manipulationProperties );

	        $mediaMover->media($this)->move();

			$this->clearImageManipulationProperties();
		});
	}

	public function convertPropertiesToString( $properties ){
		return $properties
				// sort properties alphabetically to prevent creating multiple images because of different methods order
				->sortBy(function($args, $method){
					return $method;
				})
				->transform(function($args, $method){
					$argsString = implode(',', $args);
					return "{$method}+{$argsString}";
				})
				->implode('|');
	}

}