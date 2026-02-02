<?php

use HoltBosse\Form\Fields\Checkbox\Checkbox;

beforeEach(function () {
	$_POST = [];
	$_GET = [];
});

test('can be instantiated', function () {
	$c = new Checkbox();
	expect($c)->toBeInstanceOf(Checkbox::class);
});

test('display outputs hidden and checkbox inputs and label and checked when default set', function () {
	$c = new Checkbox();
	$c->name = 'agree';
	$c->id = 'agree1';
	$c->label = 'I agree';
	$c->default = 1;

	ob_start();
	$c->display();
	$out = ob_get_clean();

	expect($out)->toContain("type='hidden'");
	expect($out)->toContain("value='0'");
	expect($out)->toContain("type='checkbox'");
	expect($out)->toContain("value='1'");
	expect($out)->toContain('I agree');
	expect($out)->toContain('checked=checked');
});

test('getFriendlyValue returns Checked/Unchecked when requested', function () {
	$c = new Checkbox();
	$c->default = 1;
	$help = new class { public $return_in_text_form = true; };

	expect($c->getFriendlyValue($help))->toBe('Checked');

	$c->default = 0;
	expect($c->getFriendlyValue($help))->toBe('Unchecked');
});

test('getFriendlyValue returns disabled checkbox html when not requested', function () {
	$c = new Checkbox();
	$c->default = 1;
	$help = new class {};

	$out = $c->getFriendlyValue($help);
	expect($out)->toContain('disabled');
	expect($out)->toContain('checked');
});

test('loadFromConfig sets filter to int validator', function () {
	$cfg = new stdClass();
	$c = new Checkbox();
	$c->loadFromConfig($cfg);

	expect($c->filter->isValid(1))->toBeTrue();
	expect($c->filter->isValid('a'))->toBeFalse();
});

test('validate returns false when required and missing, true when set', function () {
	$c = new Checkbox();
	$c->name = 'agree';
	$c->required = true;

	unset($_POST['agree']);
	expect($c->validate())->toBeFalse();

	$_POST['agree'] = '1';
	expect($c->validate())->toBeTrue();
});

