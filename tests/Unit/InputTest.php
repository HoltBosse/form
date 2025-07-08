<?php

use HoltBosse\Form\Input;

describe('Input', function () {
	describe('::stringURLSafe', function () {
		it('converts spaces and underscores to hyphens and lowercases', function () {
			expect(Input::stringURLSafe('Hello World_Test'))
				->toBe('hello-world-test');
		});
		it('removes non-alphanumeric characters', function () {
			expect(Input::stringURLSafe('Hello!@# World$%^'))
				->toBe('hello-world');
		});
		it('trims and lowercases the string', function () {
			expect(Input::stringURLSafe('  Foo Bar  '))
				->toBe('foo-bar');
		});
		it('handles multiple spaces and dashes', function () {
			expect(Input::stringURLSafe('foo   bar---baz'))
				->toBe('foo-bar-baz');
		});
	});

	describe('::stringHtmlSafe', function () {
		it('escapes HTML special characters', function () {
			expect(Input::stringHtmlSafe('<div>"Hello" & \'World\'</div>'))
				->toBe('&lt;div&gt;&quot;Hello&quot; &amp; &#039;World&#039;&lt;/div&gt;');
		});
		it('leaves safe strings unchanged', function () {
			expect(Input::stringHtmlSafe('plain text'))
				->toBe('plain text');
		});
	});

	describe('::makeAlias', function () {
		it('sanitizes and url-safes a string', function () {
			expect(Input::makeAlias('Hello World!@#'))
				->toBe('hello-world');
		});
		it('removes low ASCII and special chars', function () {
			expect(Input::makeAlias("\x01\x02Test String!"))
				->toBe('test-string');
		});
		it('handles underscores and dashes', function () {
			expect(Input::makeAlias('foo_bar-baz'))
				->toBe('foo-bar-baz');
		});
	});
});
