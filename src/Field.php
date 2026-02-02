<?php
namespace HoltBosse\Form;

Use HoltBosse\Form\Input;
use Respect\Validation\Validator as v;

// BASE CLASS FOR FIELDS
class Field {
	public $id;
	public $title;
	#[FormBuilderAttribute(fieldType: "Input", dataType: FormBuilderDataType::String, required: false)]
	public $label;
	#[FormBuilderAttribute(fieldType: "Input", dataType: FormBuilderDataType::LetterString, required: true, label: "Field Name (unique id)")]
	public $name; // unique id for form submit
	public $description;
	#[FormBuilderAttribute(fieldType: "Select", dataType: FormBuilderDataType::Bool, required: true)]
	public $required;
	public $valid;
	public $default;
	public $filter;
	public $type;
	public $logic;
	public $missingconfig;
	public $in_repeatable_form;
	public $maxlength;
	public $minlength;
	public $save;
	public $placeholder;
	public $nowrap;
	public $form;
	public $index; // used to determine POST/GET array index in repeatables

	public function display() {
		echo "<label class='label'>Field Label</label>";
		echo "<p>Hello, I am a field!</p>";
	}

	public function getRenderedName($multiple=false) {
		// output name as array if in repeatable form
		// multiple makes it an array of arrays :D -> [][]
		$rendered_name = ' name="' . $this->name;
		if ($this->in_repeatable_form!==null || $multiple) {
			if ($this->in_repeatable_form!==null && $multiple) {
				$rendered_name .= "[{{replace_with_index}}][]"; // replace string with index in js when repeatable form + is clicked
			}
			else {
				$rendered_name .= "[]";
			}
		}
		$rendered_name .=  '" ';
		return $rendered_name;
	}

	public function getRenderedForm() {
		if($this->form) {
			return "form='$this->form'";
		}

		return "";
	}

	public function validate() {
		return true;
	}

	public function isMissing() {
		$filter = Input::isValidatorRule($this->filter) ? Input::buildValidatorFromArray((array) $this->filter) : $this->filter;
		if ($this->in_repeatable_form ?? null) {
			// value will be in array
			$value = Input::filter(Input::getVar($this->name)[$this->index], $filter);
		} else {
			$value = Input::getVar($this->name, $filter);
		}

		if ($value===false && $this->required) {
			return true;
		}
		if ($value===null && $this->required) {
			return true;
		}
		if ($value==='' && $this->required) {
			return true;
		}

		return false;
	}

	public function setFromSubmit() {
		$filter = Input::isValidatorRule($this->filter) ? Input::buildValidatorFromArray((array) $this->filter) : $this->filter;
		$value = Input::getVar($this->name, $filter);
		if (is_array($value)) {
			$this->default = json_encode($value);
		} else {
			$this->default = $value;
		}
	}

	public function setFromSubmitRepeatable($index=0) {
		// index = index of repeated form inside repeatable
		$raw_value_array = Input::getVar($this->name, v::ArrayType()); // get raw array
		$value = $raw_value_array[$index]; // get nth entry in raw array
		$this->index = $index; // set repeatable field index for validation

		$this->default = $value;
		if (is_array($value)) {
			$this->default = json_encode($value);
		}
	}

	public function getFriendlyValue($helpfulInfo) {
		// return friendly (text) version of data represented by default/current value
		// ostensibly used by 'list' item option in content listings for user driven columns
		// helpful info can be anything, but something like the field config object
		// can be used to determine, for example, a content type for a contentselector etc
		$output = $this->default;
		$output = htmlspecialchars_decode($output); //due to some old junky filters, stuff was stored encoded, so decode it first
		if($helpfulInfo && $helpfulInfo->return_in_text_html_form==true) {
			return Input::stringHtmlSafe($output);
		} else {
			return $output;
		}
	}

	public function loadFromConfig($config) {
		// config is json field already converted to object by form class
		$this->type = $config->type ?? 'error!!!';
		$this->name = $config->name ?? 'error!!!';
		$this->id = $config->id ?? $this->name;
		$this->save = $config->save ?? true;
		$this->label = $config->label ?? '';
		$this->required = $config->required ?? false;
		$this->description = $config->description ?? '';
		$this->filter = $config->filter ?? v::AlwaysValid();
		$this->default = $config->default ?? $this->default;
		$this->maxlength = $config->maxlength ?? 99999;
		$this->minlength = $config->minlength ?? 0;
		$this->placeholder = $config->placeholder ?? "";
		$this->logic = $config->logic ?? '';
		$this->nowrap = $config->nowrap ?? false;
		$this->form = $config->form ?? null;

		return $this;
	}
}