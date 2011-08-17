<?php

/*
	This is simple Beanstalkd abstraction I will use some day.
*/

$pipe = new aePipeline('image-resize');
// $pipe will use 'image-resize' tube

$job = new aeJob('thumbnail/some_image.png?w=200&h=200')
	->delay(50)
	->priority(200);

$pipe->push($job);

$job = $pipe->pop(); // get next job from the queue

$job->id;	// Beantalkd ID
$job->data; // 'thumbnail/some_image.png?w=200&h=200'

$data = parse_url($job->data); parse_str($data['query'], $data['query']);

// A nice to have feature.
$stats = $pipe->command('stats'); // returns an associative array.
// OR
$tube_stats = $pipe->command('stats-tube image-resize'); // same but for this tube

$job->delay(100);
$pipe->push($job);

// $pipe->bury($job);
// $pipe->kick(1);

$job->delete();