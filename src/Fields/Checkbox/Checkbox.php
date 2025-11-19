<?php
namespace HoltBosse\Form\Fields\Checkbox;

Use HoltBosse\Form\Field;
use Respect\Validation\Validator as v;

class Checkbox extends Field {

	public function display() {
		echo "<div class='field'>";
			echo "<label for='{$this->id}' class='checkbox'>";
				echo "<input type='hidden' data-logicignore value='0' {$this->getRenderedName()} {$this->getRenderedForm()}>"; // ensure submitted value
				$required="";
				if ($this->required) {$required=" required ";}
				$checked = "";
				if ($this->default) {$checked=" checked=checked ";} // 0 value stored for unchecked
				echo "<input $checked value='1' type='checkbox' id='{$this->id}' {$this->getRenderedName()} {$this->getRenderedForm()}>";
				echo "&nbsp;" . $this->label;
			echo "</label>";
			if ($this->description) {
				echo "<p class='help'>" . $this->description . "</p>";
			}
		echo "</div>";
	}

	public function getFriendlyValue($helpful_info) {
		if($helpful_info && $helpful_info->return_in_text_form==true) {
			if ($this->default==1) {
				return "Checked";
			} else {
				return "Unchecked";
			}
		} else {
			$checked="";
			if ($this->default==1) {
				$checked=" checked ";
			}
			return "<input type='checkbox' disabled {$checked}>";
		}
	}

	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? v::IntVal();
	}

	public function validate() {
		if ($this->isMissing()) {
			return false;
		}
		return true;
	}
}
