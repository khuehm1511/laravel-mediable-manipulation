# Laravel Mediable Manipulate Media Extension:

This package is an extenstion for the well-known laravel package [Laravel Mediable]( https://github.com/plank/laravel-mediable ).

The main purpose of this package is to provide comaptible & fast functionalities to manipulate media. **Currently it supports manipulating images only**.

## Images Manipulations

Image Manupulations is being Done via [Intervention\Image](https://github.com/Intervention/image) package and you can use its methods to manipulate image

use `manipulateImage` function to manipulate images, which take method name as its first arguments and array of method args as its second arguments, for example if you want to resize the image

```php
	$media->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->applyImageManipulation()
```

this will generate a new file with the manipulation properties specified, please note that you can apply multiple manipulation properties

```php
	$media->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->manipulateImage('colorize', ['Red' => 10, 'Green' => 20, 'Blue' => 30 ] )
		->applyImageManipulation()
```

and if you want to implement two manipulations in a row please make sure to clear Image Manipulations Properties before going to the second one.

```php
	//first manipulation resize then colorize then generate
	$media->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->manipulateImage('colorize', ['Red' => 10, 'Green' => 20, 'Blue' => 30 ] )
		->applyImageManipulation();

	//second manipulation resize then colorize then draw a line
	$media
		->clearImageManipulationProperties()
		->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->manipulateImage('colorize', ['Red' => 10, 'Green' => 20, 'Blue' => 30 ] )
		->manipulateImage('line', ['x1' => '0', 'y1' => '0', 'x2' => 20, 'y2' => '20' ] )
		->applyImageManipulation();
```

#### Manipulations Results

let's suppose we have media with the follwing attributes
``` php
	$media->directory = 'foo/bar';
	$media->filename = 'baz';
	$media->filename = 'jpg';
```

if you applied manipulation on this media it won't write on the original file for the sake of keeping the original file, instead it will generate new file with the applied manipulations properties

if you applied `resize` manipulation on media then it will generate new file with the name `foo/bar/baz-resize+250,250.jpg`

if you applied `resize` and `colorize` manipulations on media then it will generate new file with the name `foo/bar/baz-colorize+10,20,30|resize+250,250.jpg`.

Please note that manipulations are sorted alphabetically and added to file names, and if you wondering why
because we wanted to avoid implementing manipulation properties on the original file each time you ask for it
instead package will search of the corresponding file and if exists package will return it. and if you wonddering why we did that in first place because you know processing/cpus/performance is expensive but storage is not

#### Media Accessers

Larave Mediable introduces multiple accessers for the media like `getUrl`, `getDiskPath` and `getAbsolutePath` so you can use them to get the manipulated files

```php
	$media->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->manipulateImage('colorize', ['Red' => 10, 'Green' => 20, 'Blue' => 30 ] )
		->applyImageManipulation()
		->getUrl()
```

the previous block of code will return a url for the file `foo/bar/baz-colorize+10,20,30|resize+250,250.jpg` relative to media disk

and if you want to get the original files after manipulation you can use `clearImageManipulationProperties` method to clear manipulation properties and get the right file

```php
	// this will return foo/bar/baz-colorize+10,20,30|resize+250,250.jpg
	$media->manipulateImage('resize',['w' => 250, 'h' => 250] )
		->manipulateImage('colorize', ['Red' => 10, 'Green' => 20, 'Blue' => 30 ] )
		->applyImageManipulation()
		->getDiskPath();

	// this will return foo/bar/baz-colorize+10,20,30|resize+250,250.jpg
	$media->getDiskPath();

	//this will return foo/bar/baz.jpg
	$media->clearImageManipulationProperties()
			->getDiskPath();
```
#### Move, Rename and Exists

if you want to move the original file form one place to another, please note that all of its related files (manipulation results) will be moved too, this will help save space on storage.

use **Laravel Mediable** methods to move and rename.

```php
	$media->move( $destination, $newFilename );
	$media->rename($newFilename);
	$media->exists();
```

#### Enabling/Disabling Image Manipulations

To enable image manipulation use method `enableImageManipulation` like `$media->enableImageManipulation()`, **functionality is enabled be default**

To disable image manipulation use method `disableImageManipulation` like `$media->disableImageManipulation()`.

#### Creating manipulations after media is created

You can create manipulations after media is created using the `registerImageManipulations` method, this method should return array of arrays that contains predefined manipulations

```php

	//in Your Media Model
	public function registerImageManipulations(){
		return [
			[
				'resize' => ['w' => 250, 'h' => 250]
			],
			[
				'resize' => ['w' => 250, 'h' => 250],
				'colorize' => ['red' => 10, 'green' => 20, 'blue' => 30]
			]
		];
	}

```

now when the model file `created` event it will apply those manipulations on media.

## What's next:

Image Manipulation is very important and this is reason why we started with it and stoped here, but we have plans to add **Video Manipulation functionality soon** untill then enjoy and give feedback.