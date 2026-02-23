<?php

use HoltBosse\Form\Form;
use HoltBosse\Form\Field;
use PHPUnit\Framework\TestCase;

// Mock Field classes for testing
class MockTextField extends Field {
	public function loadFromConfig(object $config): Field {
		parent::loadFromConfig($config);
		$this->name = $config->name;
		$this->label = $config->label ?? 'Test Field';
		$this->type = 'text';
		return $this;
	}
	public function validate(): bool { return true; }
	public function setFromSubmit(): void {}
	public function display(): void {}
}

class MockTextField2 extends Field {
	public function loadFromConfig(object $config): Field {
		parent::loadFromConfig($config);
		$this->name = $config->name;
		$this->label = $config->label ?? 'Test Field';
		$this->type = 'text';
		return $this;
	}
	public function validate(): bool { return true; }
	public function setFromSubmit(): void {}
	public function display(): void {}
}

class MockTextField3 extends Field {
	public function loadFromConfig(object $config): Field {
		parent::loadFromConfig($config);
		$this->name = $config->name;
		$this->label = $config->label ?? 'Test Field';
		$this->default = $config->default ?? 'test value';
		$this->type = 'text';
		return $this;
	}
	public function validate(): bool { return true; }
	public function setFromSubmit(): void {}
	public function display(): void {}
	public function getFriendlyValue(mixed $helpfulInfo): mixed {
		return $this->default;
	}
}

// Mock Field class for registration
test('Form loads fields from JSON and serializes correctly', function () {
	// Register a mock field type
	\HoltBosse\Form\Form::registerField('FormTestJsonTestFakeText', MockTextField::class);

	// Create a sample form JSON
	$formJson = [
		'id' => 'test_form',
		'display_name' => 'Test Form',
		'fields' => [
			(object)[
				'type' => 'FormTestJsonTestFakeText',
				'name' => 'field1',
				'label' => 'Field 1',
			]
		]
	];
	$jsonPath = __DIR__ . '/test_form.json';
	file_put_contents($jsonPath, json_encode($formJson));

	// Instantiate the Form
	$form = new Form($jsonPath);

	// Assert fields loaded
	expect($form->id)->toBe('test_form');
	expect($form->displayName)->toBe('Test Form');
	expect($form->fields['field1']->name)->toBe('field1');
	expect($form->fields['field1']->label)->toBe('Field 1');

	// Test serialization
	$json = $form->serializeJson();
	expect($json)->toBeJson();

	//we create another form with a second field, and then try and load it into the first form without the extra field to make sure no exceptions happen
	$formJsonInvalidField = $formJson;
	$formJsonInvalidField["fields"][] = (object)[
		'type' => 'FormTestJsonTestFakeText',
		'name' => 'field2',
		'label' => 'Field 2',
	];

	$jsonPathInvalidForm = __DIR__ . '/test_form_2.json';
	file_put_contents($jsonPathInvalidForm, json_encode($formJsonInvalidField));

	// Instantiate the Form
	$formInvalidJson = new Form($jsonPathInvalidForm);

	try {
		$form->deserializeJson(json_encode($formInvalidJson));
	} catch (Throwable $e) {
		$this->fail('An exception was thrown deserializing json into form: ' . $e->getMessage());
	}

	// Clean up
	unlink($jsonPath);
	unlink($jsonPathInvalidForm);
});

test('Form getFieldByName returns the correct field', function () {
	// Register a mock field type
	\HoltBosse\Form\Form::registerField('FormTestGetFieldByNameFakeText', MockTextField2::class);

	// Create a sample form JSON
	$formJson = [
		'id' => 'test_form2',
		'display_name' => 'Test Form 2',
		'fields' => [
			(object)[
				'type' => 'FormTestGetFieldByNameFakeText',
				'name' => 'field2',
				'label' => 'Field 2',
			]
		]
	];
	$jsonPath = __DIR__ . '/test_form2.json';
	file_put_contents($jsonPath, json_encode($formJson));

	// Instantiate the Form
	$form = new Form($jsonPath);

	// Test getFieldByName returns the correct field
	$field = $form->getFieldByName('field2');
	expect($field)->not()->toBeNull();
	expect($field->name)->toBe('field2');
	expect($field->label)->toBe('Field 2');

	// Test getFieldByName throws for missing field
	expect(fn() => $form->getFieldByName('does_not_exist'))->toThrow(Exception::class);

	// Clean up
	unlink($jsonPath);
});

test('Form createEmailHtml generates correct HTML', function () {
	//mock server var
	$_SERVER['SERVER_NAME'] = 'example.com';

	// Register a mock field type
	\HoltBosse\Form\Form::registerField('FormTestCreateEmailHtmlFakeText', MockTextField3::class);

	// Create a sample form JSON
	$formJson = [
		'id' => 'test_form3',
		'display_name' => 'Test Form 3',
		'fields' => [
			(object)[
				'type' => 'FormTestCreateEmailHtmlFakeText',
				'name' => 'field3',
				'label' => 'Field 3',
				'default' => 'Email Value',
			]
		]
	];
	$jsonPath = __DIR__ . '/test_form3.json';
	file_put_contents($jsonPath, json_encode($formJson));

	// Instantiate the Form
	$form = new Form($jsonPath);

	// Generate email HTML
	$bannerImage = 'https://example.com/banner.png';
	$html = $form->createEmailHtml($bannerImage);

	// Assert HTML contains expected content
	expect($html)->toContain('Test Form 3 submission');
	expect($html)->toContain('Field 3: Email Value');
	expect($html)->toContain($bannerImage);
	expect($html)->toContain('<img src="https://example.com/banner.png"');

	// Clean up
	unlink($jsonPath);
	unset($_SERVER['SERVER_NAME']);
});
