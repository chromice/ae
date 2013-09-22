<?php

ae::utilize('inspector');

// class ProbeTest {
	

// function probe_test()
// {
	$probe = ae::probe('Test probe');

	$probe->mark('started');

	// Declaring a variable would consume some memory
	$string = "Hello";
	
	echo $string;

	$probe->mark('declared a variable and echoed a string');

	$string2 = " world";

	usleep(3000);

	$probe->mark('slept for 3.0ms and declared another variable');

	echo $string2;
	
	$probe->mark('echoed another string');

	unset($probe);
// }
// }

// probe_test();

// $p = new ProbeTest();
// $p->probe_test();

// Foo started. Timestamp: 0.001ms (0.000ms). Fooprint: 0.1kb (0b)
// Foo did something. Timestamp: 0.002ms (0.001ms). Fooprint: 2kb (1.9kb)
// Foo finished. Timestamp: 0.003ms (0.002ms). Fooprint: 0.1kb (0b)