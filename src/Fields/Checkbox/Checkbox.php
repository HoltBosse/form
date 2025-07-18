<?php
namespace HoltBosse\Form\Fields\Checkbox;

Use HoltBosse\Form\Field;

class Checkbox extends Field {

	public function display(): void {
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

	public function getFriendlyValue(mixed $helpful_info): string {
		$checked="";
		if ($this->default==1) {
			$checked=" checked ";
		}
		return "<input type='checkbox' disabled {$checked}>";
	}

	public function loadFromConfig(object $config): void {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? 'NUMBER';
	}

	public function validate(): bool {
		if ($this->isMissing()) {
			return false;
		}
		return true;
	}
}
