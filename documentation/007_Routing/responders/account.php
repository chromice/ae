<?php

switch (ae::request()->segment(0, 'show'))
{
	case 'login':
		echo 'Authentication form.';
		break;

	case 'logout':
		echo 'Log user out.';
		break;
	
	case 'edit':
		echo 'Edit account information.';
		break;
	
	default:
		echo 'Display account information.';
		break;
}