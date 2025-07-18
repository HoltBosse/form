<?php
namespace HoltBosse\Form;

Use HoltBosse\Form\Input;

// BASE CLASS FOR FIELDS
class Field {
	public string $id;
	public string $title;
	public string $label;
	public string $name; // unique id for form submit
	public string $description;
	public bool $required;
	public mixed $valid;
	public mixed $default;
	public string $filter;
	public mixed $type;
	public mixed $logic;
	public mixed $missingconfig;
	public ?bool $in_repeatable_form;
	public int $maxlength;
	public int $minlength;
	public bool $save;
	public string $placeholder;
	public bool $nowrap;
	public mixed $form;
	public int $index; // used to determine POST/GET array index in repeatables

	public function display(): void {
		echo "<label class='label'>Field Label</label>";
		echo "<p>Hello, I am a field!</p>";
	}

	public function getRenderedName(bool $multiple=false): string {
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

	public function getRenderedForm(): string {
		if($this->form) {
			return "form='$this->form'";
		}

		return "";
	}

	public function validate(): bool {
		return true;
	}

	public function isMissing(): bool {
		if ($this->in_repeatable_form ?? null) {
			// value will be in array
			$value = Input::filter(Input::getVar($this->name)[$this->index], $this->filter);
		} else {
			$value = Input::getVar($this->name, $this->filter);
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

	public function setFromSubmit(): void{
		$value = Input::getVar($this->name, $this->filter);
		if (is_array($value)) {
			$this->default = json_encode($value);
		} else {
			$this->default = $value;
		}
	}

	public function setFromSubmitRepeatable(int $index=0): void {
		// index = index of repeated form inside repeatable
		$raw_value_array = Input::getVar($this->name, "ARRAYRAW"); // get raw array
		$value = $raw_value_array[$index]; // get nth entry in raw array
		$this->index = $index; // set repeatable field index for validation

		$this->default = $value;
		if (is_array($value)) {
			$this->default = json_encode($value);
		}
	}

	public function getFriendlyValue(mixed $helpfulInfo): string {
		// return friendly (text) version of data represented by default/current value
		// ostensibly used by 'list' item option in content listings for user driven columns
		// helpful info can be anything, but something like the field config object
		// can be used to determine, for example, a content type for a contentselector etc
		return $this->default;
	}

	public function loadFromConfig(object $config): void {
		// config is json field already converted to object by form class
		$this->type = $config->type ?? 'error!!!';
		$this->name = $config->name ?? 'error!!!';
		$this->id = $config->id ?? $this->name;
		$this->save = $config->save ?? true;
		$this->label = $config->label ?? '';
		$this->required = $config->required ?? false;
		$this->description = $config->description ?? '';
		$this->filter = $config->filter ?? 'RAW';
		$this->default = $config->default ?? null;
		$this->maxlength = $config->maxlength ?? 99999;
		$this->minlength = $config->minlength ?? 0;
		$this->placeholder = $config->placeholder ?? "";
		$this->logic = $config->logic ?? '';
		$this->nowrap = $config->nowrap;
		$this->form = $config->form;
	}
}