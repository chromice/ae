<?php

ae::invoke('Library');

class Library
{
	public function foo()
	{
		echo 'foo';
	}
	
	static public function bar()
	{
		AnotherLibraryClass::bar();
	}
}

class AnotherLibraryClass
{
	static public function bar()
	{
		echo 'bar';
	}
}
