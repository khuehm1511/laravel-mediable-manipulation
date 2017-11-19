<?php

namespace Missionx\Mediable\Manipulation\Helpers;

use Illuminate\Filesystem\FilesystemManager;

Class MediaMover{

	/**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * new directory to move to
     * @var string
     */
    public $directory;

    /**
     * file new name
     * @var string
     */
    public $filename;

    /**
     * Media file that will be moved
     * @var Plank\Mediable\Media
     */
    public $media;

    /**
     * Constructor.
     * @param \Illuminate\Filesystem\FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * set directory value
     * @return $this
     */
    public function toDirectory( $directory ){
    	$this->directory = trim($directory, '/');
    	return $this;
    }

    /**
     * set file new name value
     * @return $this
     */
    public function withFilename( $filename ){
    	$this->filename = $filename;
    	return $this;
    }

    /**
     * set Media
     * @return $this
     */
    public function media( $media ){
    	$this->media = $media;
    	return $this;
    }

    /**
     * get directory value
     * @return string
     */
    public function getDirectory(){
    	return $this->directory;
    }

    /**
     * get filename
     * @return $this
     */
    public function getFilename(){
    	return $this->filename;
    }

    /**
     * move file to correct destination
     * @return void
     */
    public function move(){
    	$storage = $this->filesystem->disk($this->media->disk);

    	$filename = $this->findFilename();

    	$target_path = $this->directory.'/'.$filename.'.'.$this->media->extension;

    	if ($storage->has($target_path)) {
            throw MediaMoveException::destinationExists($target_path);
        }

        $storage->move($this->media->getDiskPath(), $target_path);
    }

    /**
     * update database media record to the new name
     * @return void
     */
    public function updateDatabase(){
    	$this->media->filename = $this->filename;
        $this->media->directory = $this->directory;
        $this->media->save();
    }

    /**
     * get the correct value of filename
     * if filename is provided make sure it doesn't contains extenstion
     * if not provided get media file name
     * if media has manipulationsproperties then append them to filename
     * @return $this|$filename
     */
    public function findFilename(){
    	if( !$this->filename ){
    		$this->filename = $this->media->filename;
    	}else{
	    	$this->removeExtensionFromFilename();
    	}


    	//check if media has manipulations
    	if( $this->media->isImage() && !$this->media->getImageManipulationProperties()->isEmpty() ){
    		$filename = $this->filename.'-'.$this->media->convertPropertiesToString( $this->media->getImageManipulationProperties() );
    	}else{
    		$filename = $this->filename;
    	}

    	return $filename;
    }

    /**
     * Remove the media's extension from a filename.
     * @param  string $filename
     * @param  string $extension
     * @return string
     */
    protected function removeExtensionFromFilename()
    {
        $extension = '.'.$this->media->extension;
        $extension_length = mb_strlen($this->filename) - mb_strlen($extension);
        if (mb_strrpos($this->filename, $extension) === $extension_length) {
            $this->filename = mb_substr($this->filename, 0, $extension_length);
        }
    }

}