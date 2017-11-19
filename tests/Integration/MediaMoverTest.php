<?php

use Illuminate\Filesystem\FilesystemManager;
use Missionx\Mediable\Manipulation\Helpers\MediaMover;

Class MediaMoverTest extends TestCase{

	/**
	 * Media Model
	 * @var App\Modules\Files\Models\Media
	 */
	public $model;

	/**
	 * Media Mover Implementation
	 * @var Missionx\Mediable\Manipulation\Helpers\MediaMover
	 */
	public $mediaMover;

	public function setUp(){
		parent::setUp();

		$this->model = new Media;
    	$this->model->disk = 'tmp';
    	$this->model->directory = 'foo';
    	$this->model->filename = 'bar';
    	$this->model->extension = 'jpg';
    	$this->model->aggregate_type = Media::TYPE_IMAGE;

    	$this->mediaMover = new MediaMover( app(FilesystemManager::class) );
	}

	/** @test */
	function can_set_media()
	{
	    $this->mediaMover->media( $this->model );

	    $this->assertInstanceOf( Media::class, $this->mediaMover->media );
	}

	/** @test */
	public function can_set_and_trim_directory(){
		$this->mediaMover->toDirectory( '/foo/bar/' );
		$this->assertEquals('foo/bar', $this->mediaMover->getDirectory());
	}

	/** @test */
	public function can_set_filename(){
		$this->mediaMover->withFilename( 'baz.jpg' );
		$this->assertEquals( 'baz.jpg' ,$this->mediaMover->getFilename() );
	}

	/** @test */
	function can_remove_extension_from_filename()
	{
	    $this->mediaMover->media( $this->model )
					    ->withFilename('baz.jpg');

		self::callMethod( $this->mediaMover, 'removeExtensionFromFilename', [] );

	    $this->assertEquals( 'baz', $this->mediaMover->getFilename() );
	}

	/** @test */
	function can_find_filename_properly()
	{
		//new file name was not specified
	    $this->mediaMover->media( $this->model );
	    $this->assertEquals( 'bar', $this->mediaMover->findFilename() );

	    //new filename was specified
	    $this->mediaMover->withFilename('baz');
	    $this->assertEquals( 'baz', $this->mediaMover->findFilename() );

	    //make sure $this->filename has the correct value
	    $this->assertEquals('baz', $this->mediaMover->getFilename());

	    //new filename was not specified and has manipulation properties
	    $this->mediaMover->withFilename( null );
	    $this->model->manipulateImage('resize', [250,250]);
	    $this->model->manipulateImage('colorize',[250,250,250]);

	    $this->assertEquals( 'bar-colorize+250,250,250|resize+250,250', $this->mediaMover->findFilename() );

	    //make sure $this->filename has the correct value
	    $this->assertEquals('bar', $this->mediaMover->getFilename());

	    //new filename was specified but has no manipulation properties
	    $this->mediaMover->withFilename( 'baz' );
	    $this->assertEquals( 'baz-colorize+250,250,250|resize+250,250', $this->mediaMover->findFilename() );

	    //make sure $this->filename has the correct value
	    $this->assertEquals('baz', $this->mediaMover->getFilename());
	}

	/** @test */
	function can_be_moved_when_media_has_no_manipulation_properties()
	{
		$this->useFilesystem( 'tmp' );
		$this->useDatabase();
		$this->seedFileForMedia( $this->model );
		$this->mediaMover->media( $this->model )
						->toDirectory('foo/bar')
						->withFilename('baz')
						->move();

		$this->assertTrue( app('filesystem')->disk( $this->model->disk )->exists( "foo/bar/baz.{$this->model->extension}" ) );
	}

	/** @test */
	function can_be_moved_when_media_has_manipulation_properties()
	{
		$this->model->manipulateImage('resize', [250,250]);
	    $this->model->manipulateImage('colorize',[250,250,250]);

	    $this->useFilesystem( 'tmp' );
	    $this->useDatabase();
		$this->seedFileForMedia( $this->model );

		$this->mediaMover->media( $this->model )
						->toDirectory('foo/bar')
						->withFilename('baz')
						->move();

		$this->assertTrue( app('filesystem')
							->disk( $this->model->disk )
							->exists( "foo/bar/baz-colorize+250,250,250|resize+250,250.{$this->model->extension}" )
						);
	}


	public static function callMethod($obj, $name, array $args) {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

}