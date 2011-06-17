<?php

$process = new aeProcess('image-resize');

$job = $process->job('some_image.png?w=200&h=200')
	->delay(50)
	->priority(200);

$process->queue($job);

$job = $process->job(); // next job

$job->id;
$job->data; // raw data

// Jobs passed as URL can be parsed with parse_url()
$host = $job->url(); // full url; or schema(), user(), pass(), path(), query([name]) or fragment()
$base_url = $job->url('schema','host'); // schema+host
$x = $job->query('w'); // $x = 200;
$y = $job->query('h'); // $y = 200;

// $job->delay(100);
// $queue->queue($job);

// $queue->bury($job);
// $queue->kick(1);

$job->delete();



