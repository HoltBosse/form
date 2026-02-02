<?php
use HoltBosse\Form\Fields\Honeypot\Honeypot;

test('loadFromConfig defaults autocomplete and save', function () {
	$config = new stdClass();

	$hp = new Honeypot();
	$hp->loadFromConfig($config);

	expect($hp->autocomplete)->toBe('nothingtoseehere');
	expect($hp->save)->toBe(false);
});

test('validate returns true only when default is single space', function () {
	$hp = new Honeypot();

	$hp->default = 'not-a-space';
	expect($hp->validate())->toBeFalse();

	$hp->default = ' ';
	expect($hp->validate())->toBeTrue();
});

test('display outputs input with autocomplete, placeholder, id and value', function () {
	$hp = new Honeypot();
	$hp->id = 'hp1';
	$hp->autocomplete = 'abc';

	ob_start();
	$hp->display();
	$out = ob_get_clean();

	expect($out)->toContain('autocomplete="abc"');
	expect($out)->toContain('placeholder="Important information"');
	expect($out)->toContain("id='hp1'");
	expect($out)->toContain("value=' '");
	expect($out)->toContain('required');
});
