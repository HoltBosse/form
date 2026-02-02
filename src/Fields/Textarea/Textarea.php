<?php
namespace HoltBosse\Form\Fields\Textarea;

Use HoltBosse\Form\{Field, FormBuilderAttribute, FormBuilderDataType};
Use HoltBosse\Form\Input;
use Respect\Validation\Validator as v;

class Textarea extends Field {

	public $maxlength;
	public $minlength;
	public $select_options;
	public $input_type;
	#[FormBuilderAttribute(fieldType: "Input", dataType: FormBuilderDataType::String, required: false)]
	public $placeholder; //yes this is re-declared from parent for form builder

	public function display() {
		$hidden = "";
		if (property_exists($this,'attribute_list')) {
			$attributes = implode(' ',$this->attribute_list);
			if (in_array('hidden',$this->attribute_list)) {
				$hidden = "hidden";
			}
		}
		$required="";
		if ($this->required) {$required=" required ";}
		echo "<div class='field {$required} {$hidden}'>";
			echo "<label for='{$this->id}' class='label'>{$this->label}</label>";
			$dataValueSafe = Input::stringHtmlSafe(htmlspecialchars_decode($this->default));
			echo "<div class='control' data-value='{$dataValueSafe}'>";
				$this->default = str_replace("[NEWLINE]","\n",$this->default);
				$filterClass = is_string($this->filter) ? "filter_{$this->filter}" : "";
				echo "<textarea style='field-sizing: content;' type='{$this->input_type}' maxlength={$this->maxlength} placeholder='{$this->placeholder}' minlength={$this->minlength} class='$filterClass input' {$required} type='text' id='{$this->id}' {$this->getRenderedName()} {$this->getRenderedForm()}>";
				echo $this->default;
				echo "</textarea>";
			echo "</div>";
			if ($this->description) {
				echo "<p class='help'>" . $this->description . "</p>";
			}
		echo "</div>";
	}

	public function getFriendlyValue($helpful_info) {
		$output = $this->default;
		$output = htmlspecialchars_decode($output); //due to some old junky filters, stuff was stored encoded, so decode it first

		if($helpful_info && $helpful_info->return_in_text_html_form==true) {
			$output = Input::stringHtmlSafe($output);
			return str_replace("[NEWLINE]","<br>",$output);
		} else {
			$output = str_replace("[NEWLINE]","\n",$output);
			return $output;
		}
	}

	public function setFromSubmit() {
		parent::setFromSubmit();
		$this->default = str_replace("\n","[NEWLINE]",$this->default);
	}

	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? V::StringVal();
		$this->input_type = $config->input_type ?? 'text';

		return $this;
	}

	public function validate() {
		// TODO: enhance validation
		if ($this->isMissing()) {
			return false;
		}
		return true;
	}
}