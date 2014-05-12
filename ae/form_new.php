<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2014 Anton Muraviev <chromice@gmail.com>
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# 

// Import dependancies
ae::import('ae/request.php');

// Define options
ae::options('ae.form', array(
	'novalidate' => false // whether to prevent HTML5 validation in browsers that support it.
));

// Invoke form object
ae::invoke('aeForm');

class aeForm implements ArrayAccess
{
	public function __construct($form_id) {}
	
	// ===================
	// = Control factory =
	// ===================
	
	public function single($name) {}
	public function multiple($name) {}
	public function file($name, $destination) {}
	public function files($name, $destination) {}
	
	public function group($name) {}
	public function sequence($name, $min = 1, $max = null) {}
	
	// ==============
	// = Form state =
	// ==============
	
	public function is_submitted() {}
	
	public function initial($values) {}
	public function values() {}
	
	public function validate() {}
	public function has_errors() {}
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>') {}
	
	
	// ===============
	// = HTML output =
	// ===============
	
	public function open($attributes = array()) {}
	public function close() {}
		
	public static function attributes($attributes) {}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($name) {}
	public function offsetGet($name) {}
	public function offsetSet($name, $field) {}
	public function offsetUnset($name) {}
}

// ================
// = Field groups =
// ================

class aeFormGroup implements ArrayAccess
{
	public function __construct($name, &$form, &$values, &$errors) {}
	
	// ===================
	// = Control factory =
	// ===================
	
	public function single($name) {}
	public function multiple($name) {}
	public function file($name, $destination) {}
	public function files($name, $destination) {}
	
	// =================
	// = Control state =
	// =================
	
	public function initial($values) {}
	public function values() {}
	
	public function validate() {}
	public function has_errors() {}
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>') {}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($name) {}
	public function offsetGet($name) {}
	public function offsetSet($name, $field) {}
	public function offsetUnset($name) {}
}


class aeFormSequence extends aeFormGroup implements Iterator, Countable
{
	public function __construct($name, &$form, &$values, &$errors, $min = 1, $max = null) {}
	
	// ===================
	// = Control factory =
	// ===================

	public function single($name) {}
	public function multiple($name) {}
	public function file($name, $destination) {}
	public function files($name, $destination) {}
	
	// ============================
	// = Countable implementation =
	// ============================
	
	public function count() {}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($offset) {}
	public function offsetGet($offset) {}
	public function offsetSet($offset, $value) {}
	public function offsetUnset($offset) {}
	
	// ===========================
	// = Iterator implementation =
	// ===========================
	
	public function rewind() {}
	public function current() {}
	public function key() {}
	public function next() {}
	public function valid() {}
}


class aeFormFieldSequence implements ArrayAccess, Iterator, Countable
{
	public function __construct($name, &$form, &$values, &$errors, $min = 1, $max = null) {}
	
	// =================
	// = Control state =
	// =================
	
	public function initial($values) {}
	public function values() {}
	
	public function validate() {}
	public function has_errors() {}
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>') {}
	
	// ============================
	// = Countable implementation =
	// ============================
	
	public function count() {}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($offset) {}
	public function offsetGet($offset) {}
	public function offsetSet($offset, $value) {}
	public function offsetUnset($offset) {}
	
	// ===========================
	// = Iterator implementation =
	// ===========================
	
	public function rewind() {}
	public function current() {}
	public function key() {}
	public function next() {}
	public function valid() {}
}

// ===================
// = Field intefaces =
// ===================

interface aeValidator
{
	public function required($message, $callback = null);
}

interface aeTextValidator extends aeValidator
{
	// Primitive patters
	const month = '\d{4,}-(?:0[1-9]|1[0-2])';
	const week = '\d{4,}-W\d{2}';
	const date = '\d{4,}-\d{2}-\d{2}';
	const datetime = '\d{4,}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[\+\-]\d{2}:\d{2})?';
	const time = '(?:0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9](?:\.\d+)?';
	const integer = '[\+\-]?\d+';
	const decimal = '[\+\-]?(?:\d+(?:\.\d+)?|\.\d+)';
	const numeric = '\d+';
	const alpha = '[a-zA-Z]+';
	const alphanumeric = '[0-9a-zA-Z]+';
	const color = '#[0-9a-fA-F]{6}';
	
	// Complex patterns
	const email = '([0-9a-zA-Z]([-\+\.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})';
	const url = '((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)';
	const postcode_uk = '(([a-pr-uw-zA-PR-UW-Z]{1}[a-ik-yA-IK-Y]?)([0-9]?[a-hjks-uwA-HJKS-UW]?[abehmnprvwxyABEHMNPRVWXY]?|[0-9]?[0-9]?))\s*([0-9]{1}[abd-hjlnp-uw-zABD-HJLNP-UW-Z]{2})';
	
	public function valid_value($message, $misc);
	public function valid_pattern($message, $pattern);
	public function min_length($message, $length);
	public function max_length($message, $length);
	public function min_value($message, $limit);
	public function max_value($message, $limit);
}

interface aeFileValidator extends aeValidator
{
	public function accept($message, $types);
	public function min_size($message, $size);
	public function max_size($message, $size);
	public function min_width($message, $width);
	public function max_width($message, $width);
	public function min_height($message, $height);
	public function max_height($message, $height);
}

// ===============
// = Form fields =
// ===============

class aeFormField implements aeValidator
{
	public function __construct($name, $index, $multiple, &$form, &$values, &$errors) {}
	
	// ===============
	// = Field state =
	// ===============
	
	public function initial($value) {}
	public function value() {}
	
	public function validate() {}
	
	// ===============
	// = HTML output =
	// ===============

	public function id() {}
	public function name() {}
	
	// ==============================
	// = aeValidator implementation =
	// ==============================
	
	public function required($message, $callback = null) {}
}

class aeFormTextField extends aeFormField implements aeTextValidator
{
	// ===============
	// = Field state =
	// ===============
	
	public function has_error() {}
	public function error($before = '<em class="error">', $after = '</em>') {}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function id($value = null) {}
	
	public function input($type, $value = null, $attributes = array()) {}
	public function textarea($attributes = array()) {}
	public function select($options, $attributes = array()) {}
	
	// ==================================
	// = aeTextValidator implementation =
	// ==================================
	
	public function valid_value($message, $misc) {}
	public function valid_pattern($message, $pattern) {}
	public function min_length($message, $length) {}
	public function max_length($message, $length) {}
	public function min_value($message, $limit) {}
	public function max_value($message, $limit) {}
}

class aeFormFileField extends aeFormField implements aeFileValidator
{
	public function __construct($name, $index, $multiple, $destination, &$form, &$values, &$errors) {}
	
	// ===============
	// = Field state =
	// ===============
	
	public function has_errors() {}
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>') {}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function input($attributes = array()) {}
	
	// ==================================
	// = aeFileValidator implementation =
	// ==================================
	
	public function required($message, $callback = null) {}
	public function accept($message, $types) {}
	public function min_size($message, $size) {}
	public function max_size($message, $size) {}
	public function min_width($message, $width) {}
	public function max_width($message, $width) {}
	public function min_height($message, $height) {}
	public function max_height($message, $height) {}
}