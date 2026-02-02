<?php

use HoltBosse\Form\Fields\Input\Input;
use HoltBosse\Form\Input as coreInput;

describe('Input', function () {
	beforeEach(function () {
		// clear superglobals between tests
		$_POST = [];
		$_GET = [];
	});

	it('can be instantiated', function () {
		$i = new Input();
		expect($i)->toBeInstanceOf(Input::class);
	});

	it('renders an input with placeholder and escaped default', function () {
		$i = new Input();
		$i->name = 'myinput';
		$i->id = 'myid';
		$i->label = 'My Label';
		$i->placeholder = 'Enter text';
		$i->maxlength = 200;
		$i->minlength = 0;
		$i->default = '<script>alert(1)</script>';

		ob_start();
		$i->display();
		$out = ob_get_clean();

		expect($out)->toContain("placeholder='Enter text'");
		expect($out)->toContain("value='&lt;script&gt;alert(1)&lt;/script&gt;'" );
	});

	it('loadFromConfig sets range min/max defaults', function () {
		$i = new Input();
		$config = (object) ['input_type' => 'range'];
		$i->loadFromConfig($config);

		expect($i->min)->toBe('0');
		expect($i->max)->toBe('100');
	});

	it('attribute_list with hidden adds hidden class', function () {
		$i = new Input();
		$i->attribute_list = 'hidden something';
		$i->name = 'h';
		$i->id = 'hid';
		$i->label = 'Hidden';

		ob_start();
		$i->display();
		$out = ob_get_clean();

		expect($out)->toContain("class='field");
		expect($out)->toContain('hidden');
	});

	it('renders pattern, min/max and step attributes when set', function () {
		$i = new Input();
		$i->input_type = 'number';
		$i->min = '1';
		$i->max = '10';
		$i->step = '2';
		$i->pattern = '[0-9]+';
		$i->name = 'n';

		ob_start();
		$i->display();
		$out = ob_get_clean();

		expect($out)->toContain("pattern='[0-9]+'");
		expect($out)->toContain("min='1' max='10'");
		expect($out)->toContain("step='2'");
	});

	it('displays icon markup and parent class when enabled', function () {
		$i = new Input();
		$i->icon_status = true;
		$i->icon_parent_class = 'has-icons-right';
		$i->icon_markup = "<i class='icon'></i>";
		$i->name = 'ic';

		ob_start();
		$i->display();
		$out = ob_get_clean();

		expect($out)->toContain('has-icons-right');
		expect($out)->toContain("<i class='icon'></i>");
	});

	it('getFriendlyValue returns html-safe string when requested', function () {
		$i = new Input();
		$i->default = '<b>bold</b>';
		$helpful = new class { public $return_in_text_html_form = true; };

		$friendly = $i->getFriendlyValue($helpful);

		expect($friendly)->toBe(coreInput::stringHtmlSafe('<b>bold</b>'));
	});

	it('validate returns false when required and missing', function () {
		$i = new Input();
		$i->name = 'req';
		$i->required = true;

		unset($_POST['req']);

		expect($i->validate())->toBe(false);
	});

	it('validate returns false when default length exceeds maxlength', function () {
		$i = new Input();
		$i->default = 'abcd';
		$i->maxlength = 2;

		expect($i->validate())->toBe(false);
	});

	it('validate returns true when not required and within maxlength', function () {
		$i = new Input();
		$i->default = 'a';
		$i->maxlength = 10;
		$i->required = false;

		expect($i->validate())->toBe(true);
	});
});
