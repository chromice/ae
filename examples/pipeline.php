<?php

/*
	This is simple Beanstalkd abstraction I will use some day.
*/

$pipe = new aePipeline('image-resize');
// $pipe will use tube 'image-resize'

// $job = $process->job(array())
$job = $pipe->job('thumbnail/some_image.png?w=200&h=200')
	->delay(50)
	->priority(200);

$pipe->push($job);

$job = $pipe->pop(); // get next job from the queue

$job->id;	// a Beantalkd ID
$job->data; // 'thumbnail/some_image.png?w=200&h=200'

// A nice to have feature.
$stats = $pipe->command('stats'); // returns an associative array.
// OR
$tube_stats = $pipe->command('stats-tube image-resize'); // same but for this tube

$url = $job->url();
// OR 
// 	$url = parse_url($job->data); parse_str($url['query'], $url['query']);


$job->delay(100);
$pipe->queue($job);

// $queue->bury($job);
// $queue->kick(1);

$job->delete();



