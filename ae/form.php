<?php if (!class_exists('ae')) exit;

#
# Copyright 2011 Anton Muraviev <chromice@gmail.com>
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

ae::invoke('aeForm')

class aeForm
/*
	Web form abstraction layer.
*/
{
	public function value($field, $group = null)
	/*
		Returns a value for the field / group.
	*/
	{
		
	}
	
	public function values($group = null)
	/*
		Returns an array of values for the form or group.
	*/
	{
		# code...
	}
}

class aeFormGroup
/*
	A group of form fields.
*/
{
	
}

abstract class aeFormField
/*
	A single form field.
*/
{
	
}

interface aeFormController
{
	public function submit($form);
	/*
		This method is called when the form is submitted and validated.
	*/
	
	public function render($form, $vector);
	/*
		This method is called right before a control is rendered.
		
		It must return a rendered control (string) for the field. If vector
		points to nonexistant field (e.g. a group), it must return NULL.
	*/
	
	public function validate($form, $vector);
	/*
		This method is called for each field when the form is submitted,
		right before a control is rendered.
		
		If the form field is invalid it must return an error message for 
		the field, or `NULL` otherwise.
	*/
}