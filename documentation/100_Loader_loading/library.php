<?php

namespace ns;

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
