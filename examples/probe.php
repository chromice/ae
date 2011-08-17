<?php

error_reporting(E_ALL);

// class ProbeTest {
// 	
// 
function probe_test()
{
	$probe = ae::load('probe.php', 'probe test');
	echo memory_get_usage();
	$probe->report('initialized; did nothing');

	// Declaring variable would consume a little memory
	$string = "Hello";

	echo $string;

	$probe->report('declared and echoed a string');

	$string2 = " world";

	usleep(3000);

	$probe->report('slept for 3.0ms and declared another variable');

	echo $string2;

	unset($probe);
}
// }

probe_test();

// $p = new ProbeTest();
// $p->probe_test();

// Foo started. Timestamp: 0.001ms (0.000ms). Fooprint: 0.1kb (0b)
// Foo did something. Timestamp: 0.002ms (0.001ms). Fooprint: 2kb (1.9kb)
// Foo finished. Timestamp: 0.003ms (0.002ms). Fooprint: 0.1kb (0b)