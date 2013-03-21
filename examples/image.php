<?php 

$image = ae::image('examples/image/test.jpg');

// Get meta data
$width = $image->width();
$height = $image->height();
$type = $image->type(); // png, jpeg or gif
$mimetype = $image->mimetype();

// Test transparent PNG cropping
ae::image('examples/image/test.png')
	->crop(100,100)
	->scale(200, null)
	->suffix('_cropped')
	->save();


// Blow one pixel up.
$cropped = $image
	->crop(1,1)
	->scale($width, null) // scale proportionately.
	->save('tiny_bit.png');

// save() resets state to default, i.e. no crop, scale prefix, suffix, etc.

// Crop to cover
$image
	->align(aeImage::center, aeImage::top) // same as align(0.5, 0.5)
	->cover(100, 100)
	->prefix('cropped_')
	->save(); // save as 'cropped_test.jpg'

// Resize to fit
$image
	->fit(320, 320)
	->suffix('_small')
	->save();  // save as 'test_small.jpg'

// Apply colorize filter
// using http://uk3.php.net/manual/en/function.imagefilter.php
$image
	->apply(IMG_FILTER_COLORIZE, 55, 0, 0)
	->dispatch(); // clean all output, set the correct headers, return the image content and... die!
	
?>