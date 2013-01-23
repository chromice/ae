<?php 

$image = ae::image('examples/image/test.jpg');

// 
$width = $image->width();
$height = $image->height();
$type = $image->type(); // png, jpeg or gif
$mimetype = $image->mimetype();

var_dump($width, $height, $type, $mimetype);

// Blow one pixel up.
$image
	->crop(1,1)
	->scale($width, $height) // or scale(10, null) to scale by width only; same for height
	->save('tiny_bit.png');

return;

// Crop to cover
$image
	->align(aeImage::center, aeImage::top) // same as align(0.5, 0.5)
	->cover(100, 100)
	->prefix('cropped_')
	->save();

// Resize to 
$image
	->fit(320, 320) // or fit(320, null) to fit only width; same for height
	->suffix('_small')
	->save();	// resets all values to default, i.e. no crop, scale prefix, suffix, etc.

// Apply colorize filter
// using http://uk3.php.net/manual/en/function.imagefilter.php
$image
	->apply(IMG_FILTER_COLORIZE, 55, 0, 0)
	->suffix('_red') // that is meaninless without save()
	->dispatch(); // clean all output, set the correct headers, return the image content and... die!

?>