<?php

namespace ns;

ae::define('library', '\ns\Library');
ae::define('foo', '\ns\Foo');

class Library
{
	public function foo()
	{
		echo 'foo';
	}
	
	static public function bar()
	{
		echo 'bar';
	}
}

class Foo
{
	static function foo()
	{
		echo 'foo';
	}
}

class AnotherLibraryClass
{
	static public function bar()
	{
		echo 'bar';
	}
}
