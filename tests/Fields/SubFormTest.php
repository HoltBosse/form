<?php

use HoltBosse\Form\Fields\SubForm\SubForm;
use HoltBosse\Form\Form;
use HoltBosse\Form\Field;

beforeEach(function () {
	// Register a mock field type for testing
	$mockFieldClass = new class extends Field {
		public function loadFromConfig(object $config): self {
			parent::loadFromConfig($config);
			return $this;
		}
		public function validate(): bool { 
			return isset($this->default) && !empty($this->default);
		}
		public function setFromSubmit(): void {
			$_POST[$this->name] = $_POST[$this->name] ?? '';
			parent::setFromSubmit();
		}
		public function setFromSubmitRepeatable($index = 0): void {
			$arr_name = $this->name . '[]';
			$this->default = $_POST[$arr_name][$index] ?? '';
		}
		public function display($repeatableTemplate = false): void {
			echo "<input type='text' name='{$this->name}' value='{$this->default}' />";
		}
	};
	
	// Register the mock field type
	Form::registerField('SubFormTestMockText', get_class($mockFieldClass));

	// Create a test sub-form JSON file
	$subFormJson = [
		'id' => 'test_sub_form',
		'display_name' => 'Test Sub Form',
		'fields' => [
			(object)[
				'type' => 'SubFormTestMockText',
				'name' => 'sub_field1',
				'label' => 'Sub Field 1',
				'save' => true,
			],
			(object)[
				'type' => 'SubFormTestMockText',
				'name' => 'sub_field2',
				'label' => 'Sub Field 2',
				'save' => true,
			]
		]
	];
	$this->subFormPath = __DIR__ . '/../test_sub_form.json';
	file_put_contents($this->subFormPath, json_encode($subFormJson));
});

afterEach(function () {
	// Clean up the test sub-form file
	if (file_exists($this->subFormPath)) {
		unlink($this->subFormPath);
	}
	// Clean up POST data
	$_POST = [];
});

describe('SubForm Field', function () {
	it('can be instantiated', function () {
		$subForm = new SubForm();
		expect($subForm)->toBeInstanceOf(SubForm::class);
		expect($subForm)->toBeInstanceOf(Field::class);
	});

	it('has required properties', function () {
		$subForm = new SubForm();
		expect($subForm)->toHaveProperty('form_path');
		expect($subForm)->toHaveProperty('sub_form');
		expect($subForm)->toHaveProperty('forms');
		expect($subForm)->toHaveProperty('form_base_path');
	});

	it('loads form_path from config', function () {
		$subForm = new SubForm();
		$config = (object) [
			'type' => 'SubForm',
			'name' => 'my_subform',
			'label' => 'My SubForm',
			'form_path' => 'path/to/subform.json',
			'nowrap' => false,
			'form' => null,
		];
		$subForm->loadFromConfig($config);
		
		expect($subForm->form_path)->toBe('path/to/subform.json');
		expect($subForm->name)->toBe('my_subform');
		expect($subForm->label)->toBe('My SubForm');
	});

	it('loads form_path with empty string if not provided', function () {
		$subForm = new SubForm();
		$config = (object) [
			'type' => 'SubForm',
			'name' => 'my_subform',
			'label' => 'My SubForm',
			'nowrap' => false,
			'form' => null,
		];
		$subForm->loadFromConfig($config);
		
		expect($subForm->form_path)->toBe('');
	});

	it('displays subform HTML with container', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_1';
		$subForm->label = 'Test SubForm Label';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';
		$subForm->required = false;

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('<label for=\'test_subform_1\' class=\'label\'>');
		expect($output)->toContain('Test SubForm Label');
		expect($output)->toContain('subform_container');
		expect($output)->toContain('id=\'subform_container_test_sub_form\'');
		expect($output)->toContain('add_new_repeatable');
		expect($output)->toContain('class=\'add_new_repeatable button btn is-primary\'>+</button>');
	});

	it('displays subform with existing data', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_2';
		$subForm->label = 'Test SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		
		// Simulate existing saved data
		$existingData = [
			[
				['name' => 'sub_field1', 'value' => 'value1'],
				['name' => 'sub_field2', 'value' => 'value2']
			],
			[
				['name' => 'sub_field1', 'value' => 'value3'],
				['name' => 'sub_field2', 'value' => 'value4']
			]
		];
		$subForm->default = json_encode($existingData);

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('subform_container');
		expect($output)->toContain('<div class=\'subform\'>');
		// Should have repeat buttons
		expect($output)->toContain('sf_close');
		expect($output)->toContain('sf_up');
		expect($output)->toContain('sf_down');
	});

	it('generates repeat buttons', function () {
		$subForm = new SubForm();
		
		ob_start();
		$subForm->generateRepeatButtons();
		$output = ob_get_clean();

		expect($output)->toContain('<button type=\'button\'');
		expect($output)->toContain('sf_close');
		expect($output)->toContain('remove_repeater');
		expect($output)->toContain('sf_up');
		expect($output)->toContain('sf_down');
		expect($output)->toContain('this.closest(".subform").remove();');
	});

	it('sets data from submit with no repeatable forms', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		// Simulate no form submission
		$_POST = [];

		$subForm->setFromSubmit();

		expect($subForm->forms)->toBeArray();
		expect($subForm->forms)->toHaveCount(0);
		expect($subForm->default)->toBe(json_encode([]));
	});

	it('sets data from submit with one repeatable form', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		// Simulate form submission with one repeatable
		$_POST['form_test_sub_form'] = [1];
		$_POST['sub_field1[]'] = ['First Value'];
		$_POST['sub_field2[]'] = ['Second Value'];

		$subForm->setFromSubmit();

		expect($subForm->forms)->toBeArray();
		expect($subForm->forms)->toHaveCount(1);
		expect($subForm->forms[0])->toBeInstanceOf(Form::class);
		expect($subForm->forms[0]->fields['sub_field1']->default)->toBe('First Value');
		expect($subForm->forms[0]->fields['sub_field2']->default)->toBe('Second Value');
	});

	it('sets data from submit with multiple repeatable forms', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		// Simulate form submission with three repeatables
		$_POST['form_test_sub_form'] = [1, 2, 3];
		$_POST['sub_field1[]'] = ['Value 1A', 'Value 1B', 'Value 1C'];
		$_POST['sub_field2[]'] = ['Value 2A', 'Value 2B', 'Value 2C'];

		$subForm->setFromSubmit();

		expect($subForm->forms)->toBeArray();
		expect($subForm->forms)->toHaveCount(3);
		expect($subForm->forms[0]->fields['sub_field1']->default)->toBe('Value 1A');
		expect($subForm->forms[0]->fields['sub_field2']->default)->toBe('Value 2A');
		expect($subForm->forms[1]->fields['sub_field1']->default)->toBe('Value 1B');
		expect($subForm->forms[1]->fields['sub_field2']->default)->toBe('Value 2B');
		expect($subForm->forms[2]->fields['sub_field1']->default)->toBe('Value 1C');
		expect($subForm->forms[2]->fields['sub_field2']->default)->toBe('Value 2C');
	});

	it('validates all sub-forms successfully', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		// Set up valid data
		$_POST['form_test_sub_form'] = [1, 2];
		$_POST['sub_field1[]'] = ['Valid Value 1', 'Valid Value 2'];
		$_POST['sub_field2[]'] = ['Valid Value A', 'Valid Value B'];

		$subForm->setFromSubmit();
		$result = $subForm->validate();

		expect($result)->toBeTrue();
	});

	it('validates and returns false when sub-form is invalid', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		// Set up data with one invalid form (empty values)
		$_POST['form_test_sub_form'] = [1, 2];
		$_POST['sub_field1[]'] = ['Valid Value', '']; // Second one is empty
		$_POST['sub_field2[]'] = ['Valid Value', '']; // Second one is empty

		$subForm->setFromSubmit();
		$result = $subForm->validate();

		expect($result)->toBeFalse();
	});

	it('displays with required class', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_req';
		$subForm->label = 'Required SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';
		$subForm->required = true;

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('class=\'field');
		expect($output)->toContain('required');
	});

	it('displays with description', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_desc';
		$subForm->label = 'SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';
		$subForm->description = 'This is a helpful description';

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('<p class=\'help\'>');
		expect($output)->toContain('This is a helpful description');
	});

	it('includes JavaScript for adding new repeatables', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_js';
		$subForm->label = 'SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('<script type="module">');
		expect($output)->toContain('repeatableMarkup');
		expect($output)->toContain('add_repeater');
		expect($output)->toContain('addEventListener(\'click\'');
		expect($output)->toContain('createContextualFragment');
		expect($output)->toContain('updateAllFieldLogic');
	});

	it('includes JavaScript for reordering subforms', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_reorder';
		$subForm->label = 'SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('sf_up');
		expect($output)->toContain('sf_down');
		expect($output)->toContain('insertBefore');
		expect($output)->toContain('previousElementSibling');
		expect($output)->toContain('nextElementSibling');
		expect($output)->toContain('Already at top!');
		expect($output)->toContain('Already at bottom!');
	});

	it('includes CSS styles', function () {
		$subForm = new SubForm();
		$subForm->id = 'test_subform_css';
		$subForm->label = 'SubForm';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';
		$subForm->default = '';

		ob_start();
		$subForm->display();
		$output = ob_get_clean();

		expect($output)->toContain('<style>');
		expect($output)->toContain('</style>');
	});

	it('sets formPath property on sub-forms during setFromSubmit', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		$_POST['form_test_sub_form'] = [1];
		$_POST['sub_field1[]'] = ['Value'];
		$_POST['sub_field2[]'] = ['Value'];

		$subForm->setFromSubmit();

		expect($subForm->forms[0]->formPath)->toBe($this->subFormPath);
	});

	it('handles form_base_path correctly', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = '../test_sub_form.json';
		$subForm->form_base_path = __DIR__ . '/';

		$_POST['form_test_sub_form'] = [1];
		$_POST['sub_field1[]'] = ['Value'];
		$_POST['sub_field2[]'] = ['Value'];

		$subForm->setFromSubmit();

		expect($subForm->forms)->toHaveCount(1);
		expect($subForm->forms[0])->toBeInstanceOf(Form::class);
	});

	it('serializes to JSON correctly', function () {
		$subForm = new SubForm();
		$subForm->name = 'test_subform';
		$subForm->form_path = $this->subFormPath;
		$subForm->form_base_path = '';

		$_POST['form_test_sub_form'] = [1, 2];
		$_POST['sub_field1[]'] = ['Value A', 'Value B'];
		$_POST['sub_field2[]'] = ['Value X', 'Value Y'];

		$subForm->setFromSubmit();
		
		$json = $subForm->default;
		$decoded = json_decode($json);

		expect($json)->toBeJson();
		expect($decoded)->toBeArray();
		expect($decoded)->toHaveCount(2);
	});

	it('validates empty subform as valid by default', function () {
		$subForm = new SubForm();
		$subForm->forms = [];

		expect($subForm->validate())->toBeTrue();
	});
});
