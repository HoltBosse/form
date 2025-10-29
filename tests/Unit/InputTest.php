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

	describe('::sprintfHtmlSafe', function () {
		it('formats string with HTML escaped arguments', function () {
			expect(Input::sprintfHtmlSafe('Hello %s', '<script>alert("xss")</script>'))
				->toBe('Hello &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
		});

		it('handles multiple string arguments', function () {
			expect(Input::sprintfHtmlSafe('%s says "%s"', '<user>', 'Hello & Goodbye'))
				->toBe('&lt;user&gt; says "Hello &amp; Goodbye"');
		});

		it('leaves non-string arguments unchanged', function () {
			expect(Input::sprintfHtmlSafe('Number: %d, Float: %.2f', 42, 3.14159))
				->toBe('Number: 42, Float: 3.14');
		});

		it('handles objects with __toString method', function () {
			$obj = new class {
				public function __toString() {
					return '<span>Object</span>';
				}
			};
			expect(Input::sprintfHtmlSafe('Object: %s', $obj))
				->toBe('Object: &lt;span&gt;Object&lt;/span&gt;');
		});

		it('handles mixed argument types', function () {
			expect(Input::sprintfHtmlSafe('%s has %d items & %.1f%% complete', '<user>', 5, 75.5))
				->toBe('&lt;user&gt; has 5 items & 75.5% complete');
		});

		it('handles empty format string', function () {
			expect(Input::sprintfHtmlSafe(''))
				->toBe('');
		});

		it('handles format string with no arguments', function () {
			expect(Input::sprintfHtmlSafe('Plain text'))
				->toBe('Plain text');
		});
	});

	describe('::printfHtmlSafe', function () {
		it('outputs HTML escaped content and returns length', function () {
			ob_start();
			$length = Input::printfHtmlSafe('Hello %s', '<script>');
			$output = ob_get_clean();
			
			expect($output)->toBe('Hello &lt;script&gt;');
			expect($length)->toBe(20); // Length of escaped output
		});

		it('returns correct length for multi-byte characters', function () {
			ob_start();
			$length = Input::printfHtmlSafe('Test: %s', 'café & résumé');
			$output = ob_get_clean();
			
			expect($output)->toBe('Test: café &amp; résumé');
			expect($length)->toBe(26); // Length of actual output
		});

		it('handles empty output', function () {
			ob_start();
			$length = Input::printfHtmlSafe('');
			$output = ob_get_clean();
			
			expect($output)->toBe('');
			expect($length)->toBe(0);
		});

		it('outputs multiple arguments correctly', function () {
			ob_start();
			$length = Input::printfHtmlSafe('%s: %d & %s', '<tag>', 42, '"value"');
			$output = ob_get_clean();
			
			expect($output)->toBe('&lt;tag&gt;: 42 & &quot;value&quot;');
			expect($length)->toBe(35);
		});
	});

	describe('::tuplesToAssoc', function () {
		it('converts array of tuples to associative array', function () {
			$input = [
				['key' => 'name', 'value' => 'John'],
				['key' => 'email', 'value' => 'john@example.com'],
				['key' => 'age', 'value' => '25']
			];
			$expected = [
				'name' => 'John',
				'email' => 'john@example.com',
				'age' => '25'
			];
			expect(Input::tuplesToAssoc($input))
				->toBe($expected);
		});

		it('filters out empty string values', function () {
			$input = [
				['key' => 'name', 'value' => 'John'],
				['key' => 'email', 'value' => ''],
				['key' => 'age', 'value' => '25']
			];
			$expected = [
				'name' => 'John',
				'age' => '25'
			];
			expect(Input::tuplesToAssoc($input))
				->toBe($expected);
		});

		it('filters out null values', function () {
			$input = [
				['key' => 'name', 'value' => 'John'],
				['key' => 'email', 'value' => null],
				['key' => 'age', 'value' => '25']
			];
			$expected = [
				'name' => 'John',
				'age' => '25'
			];
			expect(Input::tuplesToAssoc($input))
				->toBe($expected);
		});

		it('filters out false values', function () {
			$input = [
				['key' => 'name', 'value' => 'John'],
				['key' => 'active', 'value' => false],
				['key' => 'age', 'value' => '25']
			];
			$expected = [
				'name' => 'John',
				'age' => '25'
			];
			expect(Input::tuplesToAssoc($input))
				->toBe($expected);
		});

		it('includes zero values', function () {
			$input = [
				['key' => 'name', 'value' => 'John'],
				['key' => 'count', 'value' => 0],
				['key' => 'score', 'value' => '0']
			];
			$expected = [
				'name' => 'John',
				'count' => 0,
				'score' => '0'
			];
			expect(Input::tuplesToAssoc($input))
				->toBe($expected);
		});

		it('returns empty array for non-array input', function () {
			expect(Input::tuplesToAssoc('not an array'))
				->toBe([]);
			expect(Input::tuplesToAssoc(null))
				->toBe([]);
			expect(Input::tuplesToAssoc(123))
				->toBe([]);
		});

		it('handles empty array input', function () {
			expect(Input::tuplesToAssoc([]))
				->toBe([]);
		});
	});
});
