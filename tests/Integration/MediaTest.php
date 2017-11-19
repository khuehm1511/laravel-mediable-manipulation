<?php

use Illuminate\Support\Collection;
use Intervention\Image\ImageManager;
use Intervention\Image\Facades\Image;

class MediaTest extends TestCase
{

	/**
	 * Media Model
	 * @var App\Modules\Files\Models\Media
	 */
	public $model;

    public function setUp(){
    	parent::setUp();

    	$this->model = new Media;
    	$this->model->disk = 'tmp';
    	$this->model->directory = 'foo';
    	$this->model->filename = 'bar';
    	$this->model->extension = 'jpg';
        $this->model->mime_type = 'image/jpeg';
        $this->model->size = '1024';
    	$this->model->aggregate_type = Media::TYPE_IMAGE;
    }

    /** @test */
    public function test_is_image(){
    	$this->assertTrue( $this->model->isImage() );

    	$this->model->aggregate_type = Media::TYPE_IMAGE_VECTOR;
    	$this->assertfalse( $this->model->isImage() );
    }

    /** @test */
    function ImageManipulationProperties_variable_is_a_collection()
    {
    	$this->assertInstanceOf( Collection::class , $this->model->getImageManipulationProperties());
    }

    /** @test */
    function image_manipulation_properties_are_being_saved()
    {
    	$this->assertEquals(0, $this->model->getImageManipulationProperties()->count());

    	$this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);

    	$this->assertEquals(1, $this->model->getImageManipulationProperties()->count());
    }

    /** @test */
    function image_manipulation_properties_can_be_set(){
    	$this->model->setImageManipulationProperties( [
    			'resize' => [250,250],
    			'colorize' => [0,0,0,0]
    		]);

    	$this->assertEquals( [
    			'resize' => [250,250],
    			'colorize' => [0,0,0,0]
    		],$this->model->getImageManipulationProperties()->all() );
    }

    /** @test */
    function image_manipulation_properties_can_be_cleared()
    {
    	$this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
    	$this->model->clearImageManipulationProperties();
    	$this->assertEquals(0, $this->model->getImageManipulationProperties()->count());
    }

    /** @test */
    function test_generate_basename_with_manipulation_properties_when_image_manipulation_is_enabled()
    {
    	$this->assertEquals('bar.jpg', $this->model->getManipulatedImageBasename());

        $this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
        $this->assertEquals('bar-resize+250,250.jpg',$this->model->getManipulatedImageBasename());

        $this->model->manipulateImage('colorize', ['red'=> '0', 'green' => '0', 'blue' => '0']);
        $this->assertEquals('bar-colorize+0,0,0|resize+250,250.jpg',$this->model->getManipulatedImageBasename());

        $this->model->manipulateImage('line', ['x1'=> '0', 'y1' => '0', 'x2' => 20, 'y2'=> 20]);
        $this->assertEquals('bar-colorize+0,0,0|line+0,0,20,20|resize+250,250.jpg',$this->model->getManipulatedImageBasename());
    }

    /** @test */
    function test_generate_basename_with_manipulation_properties_when_image_manipulation_is_disabled(){
        // test with image manipulation disabled
        $this->model->disableImageManipulation();

        $this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
        $this->assertEquals('bar.jpg',$this->model->getManipulatedImageBasename());

        $this->model->manipulateImage('colorize', ['red'=> '0', 'green' => '0', 'blue' => '0']);
        $this->assertEquals('bar.jpg',$this->model->getManipulatedImageBasename());

        $this->model->manipulateImage('line', ['x1'=> '0', 'y1' => '0', 'x2' => 20, 'y2'=> 20]);
        $this->assertEquals('bar.jpg',$this->model->getManipulatedImageBasename());
    }

    /** @test */
    function can_get_image_disk_path_properly_when_image_manipulation_is_enabled()
    {
        $this->assertEquals('foo/bar.jpg', $this->model->getDiskPath());

        $this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
        $this->assertEquals('foo/bar-resize+250,250.jpg',$this->model->getDiskPath());

        $this->model->manipulateImage('colorize', ['red'=> '0', 'green' => '0', 'blue' => '0']);
        $this->assertEquals('foo/bar-colorize+0,0,0|resize+250,250.jpg',$this->model->getDiskPath());
    }

    /** @test */
    function can_get_image_disk_path_properly_when_image_manipulation_is_dsiabeld(){
        // test with image manipulation disabled
        $this->model->disableImageManipulation();

        $this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
        $this->assertEquals('foo/bar.jpg',$this->model->getDiskPath());

        $this->model->manipulateImage('colorize', ['red'=> '0', 'green' => '0', 'blue' => '0']);
        $this->assertEquals('foo/bar.jpg',$this->model->getDiskPath());
    }

    /** @test */
    function can_get_image_disk_path_properly_when_is_not_an_image(){
        // test when not an image
        $this->model->enableImageManipulation();
        $this->model->aggregate_type = Media::TYPE_IMAGE_VECTOR;

        $this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
        $this->assertEquals('foo/bar.jpg',$this->model->getDiskPath());

        $this->model->manipulateImage('colorize', ['red'=> '0', 'green' => '0', 'blue' => '0']);
        $this->assertEquals('foo/bar.jpg',$this->model->getDiskPath());
    }

    /** @test */
    function media_image_has_path_accessors()
    {
    	$this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
    	$this->assertEquals(storage_path('tmp/foo/bar-resize+250,250.jpg'), $this->model->getAbsolutePath());

    	//test when image manipulation is disabled
    	$this->model->disableImageManipulation();
    	$this->assertEquals(storage_path('tmp/foo/bar.jpg'), $this->model->getAbsolutePath());

    	//test when is not an image
    	$this->model->aggregate_type = Media::TYPE_IMAGE_VECTOR;
    	$this->assertEquals(storage_path('tmp/foo/bar.jpg'), $this->model->getAbsolutePath());
    }

    /** @test */
    function test_applyImageManipulator_has_no_errors()
    {
    	$imageManager = Mockery::mock(ImageManager::class);
    	$imageManager->shouldReceive('resize')->with(250,250)->andReturn(true);
    	$imageManager->shouldReceive('save')->andReturn(true);

    	Image::shouldReceive('make')->andReturn($imageManager);

    	$this->model->manipulateImage('resize', ['w'=> 250, 'h' => 250]);
    	$this->model->applyImageManipulation();

    	$this->assertTrue(true);
    }

    /** @test */
    function can_get_manipulation_logs_disk_path_properly()
    {
        $this->assertEquals( 'foo/bar.jpg.json', $this->model->manipulationLogsDiskPath() );
    }

    /** @test */
    function can_get_manipulation_logs_content_properly(){
        app('filesystem')->disk($this->model->disk)->delete( $this->model->manipulationLogsDiskPath() );

        $this->assertTrue( is_array( $this->model->manipulationsLogs() ) );

        //return empty array if manipulation logs doesn't exists
        $this->assertEquals([], $this->model->manipulationsLogs());
    }

    /** @test */
    function image_manipulation_properties_can_be_logged()
    {

        $this->model->logManipulationProperties( ['resize' => [250,250],'colorize'=> [155,155,155] ] );

        $this->assertTrue( is_array( $this->model->manipulationsLogs() ) );

        $this->assertEquals([ ['resize' => [250,250],'colorize'=> [155,155,155] ] ], $this->model->manipulationsLogs());

        $this->model->logManipulationProperties( ['resize' => [250,250],'colorize'=> [155,155,155], 'line' => [20,20,40,40] ] );

        $this->assertEquals([
            ['resize' => [250,250],'colorize'=> [155,155,155] ],
            ['resize' => [250,250],'colorize'=> [155,155,155], 'line' => [20,20,40,40] ]
        ], $this->model->manipulationsLogs());
    }

    /** @test */
    public function image_manipulations_can_be_moved_on_disk()
    {
        $storage = app('filesystem')->disk($this->model->disk);
        $storage->delete( $this->model->manipulationLogsDiskPath() );

        $this->useFilesystem('tmp');
        $this->useDatabase();
        $this->seedFileForMedia($this->model);

        //mock Intervention\Image
        $imageManager = Mockery::mock(ImageManager::class);
        $imageManager->shouldReceive('resize')->with(250,250)->andReturn(true);
        $imageManager->shouldReceive('colorize')->with(250,250,250)->andReturn(true);
        $imageManager->shouldReceive('save')->andReturn(true);
        Image::shouldReceive('make')->andReturn($imageManager);

        //generate image manipulations
        $this->model->manipulateImage('resize',[250,250])
                    ->applyImageManipulation();

        //since we mocking the manipulation part we must create a file with the manipulation properties
        $this->seedFileForMedia($this->model);

        $this->model->manipulateImage('resize',[250,250])
                    ->manipulateImage('colorize',[250,250,250])
                    ->applyImageManipulation();

        //since we mocking the manipulation part we must create a file with the manipulation properties
        $this->seedFileForMedia($this->model);

        $this->model->move('alpha/beta','baz.jpg');
        $this->assertEquals('alpha/beta/baz-colorize+250,250,250|resize+250,250.jpg', $this->model->getDiskPath());
        $this->assertTrue( $this->model->exists() );

        $this->model->clearImageManipulationProperties();
        $this->assertEquals('alpha/beta/baz.jpg', $this->model->getDiskPath());
        $this->assertTrue( $this->model->exists() );

        $this->assertTrue(true);
    }
}