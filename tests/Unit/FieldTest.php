<?php

use HoltBosse\Form\Field;
use HoltBosse\Form\Input;
use PHPUnit\Framework\TestCase;

describe('Field', function () {
	it('can be instantiated', function () {
		$field = new Field();
		expect($field)->toBeInstanceOf(Field::class);
	});

	it('sets and gets default value from setFromSubmit', function () {
		$field = new Field();
		$field->loadFromConfig((object) ["nowrap"=>false, "form"=>null]);

		//fake some data inputed
		$_POST['testfield'] = 'testval'; // Simulate form submission

		$field->name = 'testfield';
		$field->setFromSubmit();
		expect($field->default)->toBe('testval');

		unset($_POST['testfield']); // Clean up
	});

	it('sets default as json for array in setFromSubmit', function () {
		$field = new Field();
		$field->loadFromConfig((object) ["nowrap"=>false, "form"=>null]);
		
		//fake some data inputed
		$_POST['arrfield'] = ['a', 'b']; // Simulate form submission

		$field->name = 'arrfield';
		$field->setFromSubmit();
		expect($field->default)->toBe(json_encode(['a', 'b']));
		
		unset($_POST['arrfield']); // Clean up
	});

	it('getFriendlyValue returns default', function () {
		$field = new Field();
		$field->default = 'friendly';
		expect($field->getFriendlyValue(null))->toBe('friendly');
	});

	it('loadFromConfig sets properties', function () {
		$field = new Field();
		$config = (object) [
			'type' => 'text',
			'name' => 'myfield',
			'id' => 'id1',
			'save' => false,
			'label' => 'Label',
			'required' => true,
			'description' => 'desc',
			'filter' => 'RAW',
			'default' => 'def',
			'maxlength' => 10,
			'minlength' => 2,
			'placeholder' => 'ph',
			'logic' => 'logic',
			'nowrap' => true,
			'form' => 'formid',
		];
		$field->loadFromConfig($config);
		expect($field->type)->toBe('text');
		expect($field->name)->toBe('myfield');
		expect($field->id)->toBe('id1');
		expect($field->save)->toBe(false);
		expect($field->label)->toBe('Label');
		expect($field->required)->toBe(true);
		expect($field->description)->toBe('desc');
		expect($field->filter)->toBe('RAW');
		expect($field->default)->toBe('def');
		expect($field->maxlength)->toBe(10);
		expect($field->minlength)->toBe(2);
		expect($field->placeholder)->toBe('ph');
		expect($field->logic)->toBe('logic');
		expect($field->nowrap)->toBe(true);
		expect($field->form)->toBe('formid');
	});

	it('validate returns true by default', function () {
		$field = new Field();
		expect($field->validate())->toBeTrue();
	});
});
