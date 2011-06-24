<?php

$process = new aeProcess('image-resize');

$job = $process->job('thumbnail/some_image.png?w=200&h=200')
	->delay(50)
	->priority(200);

$process->queue($job);

$job = $process->job(); // next job

$job->id;
$job->data; // raw data

if ($job->format() !== 'json') // or ($job->format() === 'path')
{
	// Path format
	$path = $job->path(); // "thumbnail/some_image.png"
	$x = $job->paremeter('w'); // $x = 200;
	$y = $job->paremeter('h'); // $y = 200;
} 
else
{
	// JSON format
	$data = $job->decode();
}



$job->delay(100);
$queue->queue($job);

// $queue->bury($job);
// $queue->kick(1);

$job->delete();



