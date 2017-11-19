<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
	protected function getPackageProviders($app)
    {
        return [
            Plank\Mediable\MediableServiceProvider::class,
            Intervention\Image\ImageServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MediaUploader' => Plank\Mediable\MediaUploaderFacade::class,
            'Image' => Intervention\Image\Facades\Image::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
            $dotenv->load();
        }
        //use in-memory database
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);

        $app['config']->set('database.default', 'testing');

        $app['config']->set('filesystems.disks', [
            //private local storage
            'tmp' => [
                'driver' => 'local',
                'root' => storage_path('tmp'),
            ],
        ]);

        $app['config']->set('mediable.allowed_disks', [
            'tmp'
        ]);
    }

    protected function getPrivateProperty($class, $property_name)
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property_name);
        $property->setAccessible(true);
        return $property;
    }

    protected function getPrivateMethod($class, $method_name)
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);
        $method->setAccessible(true);
        return $method;
    }

	protected function seedFileForMedia(Media $media, $contents = '')
    {
        app('filesystem')->disk($media->disk)->put($media->getDiskPath(), $contents);
    }

	protected function useDatabase()
    {
        $artisan = $this->app->make(Kernel::class);
        $this->app->useDatabasePath(dirname(__DIR__));
        //Remigrate all database tables

        try{
            if( !class_exists('CreateMediableTables') ){
                require( dirname(__DIR__).'/vendor/plank/laravel-mediable/migrations/2016_06_27_000000_create_mediable_tables.php' );
            }

            $model = app( CreateMediableTables::class );

            // $model->down();
            $model->up();

        }catch(\Exception $e){

        }
    }

    protected function useFilesystem($disk)
    {
        if (!$this->app['config']->has('filesystems.disks.' . $disk)) {
            return;
        }
        $root = $this->app['config']->get('filesystems.disks.' . $disk . '.root');
        $filesystem =  $this->app->make(Filesystem::class);
        $filesystem->cleanDirectory($root);
    }
}