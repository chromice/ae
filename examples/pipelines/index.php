<?php

/*
	This is simple Beanstalkd abstraction I will some day use.
*/

$pipe = new aePipeline('image-resize');

// $job = $process->job(array())
$job = $pipe->job('thumbnail/some_image.png?w=200&h=200')
	->delay(50)
	->priority(200);

$pipe->push($job);

$job = $pipe->pop(); // get next job from the queue

$job->id;
$job->data; // 'thumbnail/some_image.png?w=200&h=200'

$url = $job->url();
// OR 
// 	$url = parse_url($job->data); parse_str($url['query'], $url['query']);


$job->delay(100);
$pipe->queue($job);

// $queue->bury($job);
// $queue->kick(1);

$job->delete();



