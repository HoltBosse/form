<?php
namespace HoltBosse\Form\Fields\Textarea;

Use HoltBosse\Form\Field;
Use HoltBosse\Form\Input;

class Textarea extends Field {

	public int $maxlength;
	public int $minlength;
	public string $input_type;

	public function display(): void {
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
				echo "<textarea oninput='this.parentNode.dataset.value = this.value;' type='{$this->input_type}' maxlength={$this->maxlength} placeholder='{$this->placeholder}' minlength={$this->minlength} class='filter_{$this->filter} input autogrowingtextarea' {$required} type='text' id='{$this->id}' {$this->getRenderedName()} {$this->getRenderedForm()}>";
				echo $this->default;
				echo "</textarea>";
			echo "</div>";
			if ($this->description) {
				echo "<p class='help'>" . $this->description . "</p>";
			}
		echo "</div>";
	}

	public function get_friendly_value(mixed $helpful_info): string {
		if($this->filter=="RAW" && $helpful_info && $helpful_info->return_in_text_form!=true) {
			return Input::stringHtmlSafe($this->default);
		} else {
			return $this->default;
		}
	}

	public function loadFromConfig(object $config): void {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? 'TEXTAREA';
		$this->input_type = $config->input_type ?? 'text';
	}

	public function validate(): bool {
		// TODO: enhance validation
		if ($this->isMissing()) {
			return false;
		}
		return true;
	}
}