<?php
namespace HoltBosse\Form\Fields\Input;

Use HoltBosse\Form\{Field, FormBuilderAttribute, FormBuilderDataType};
Use HoltBosse\Form\Input as coreInput;

class Input extends Field {
	public ?string $pattern = null;
	public ?string $input_type = null;
	public ?string $min = null;
	public ?string $max = null;
	public ?string $attribute_list = null;
	public ?string $step = null;
	public ?bool $icon_status = null;
	public ?string $icon_parent_class = null;
	public ?string $icon_markup = null;
	#[FormBuilderAttribute(fieldType: "Input", dataType: FormBuilderDataType::String, required: false)]
	public ?string $placeholder = null; //yes this is re-declared from parent for form builder

	public function display(): void {
		$hidden = "";
		$required="";
		$pattern="";
		if ($this->pattern) {$pattern="pattern='{$this->pattern}'"; };
		if ($this->required) {$required=" required ";}
		if ($this->attribute_list!="") {
			$attributes = explode(' ',$this->attribute_list);
			if (in_array('hidden',$attributes)) {
				$hidden = "hidden";
			}
		}
		echo "<div class='field {$required} {$hidden}'>";
			echo "<label for='{$this->id}' class='label'>{$this->label}</label>";
			echo "<div class='control " . ($this->icon_status ? $this->icon_parent_class : false) . "'>";
				
				if ($this->input_type=='date') {
					if ($this->default>0) {
						$this->default = date("Y-m-d", strtotime($this->default));
					}
					else {
						$this->default = "";
					}
				}
				$minmax="";
				if (isset($this->min)) {
					$minmax=" min='{$this->min}' max='{$this->max}' ";
				}
				$step="";
				if (isset($this->step)) {
					$step=" step='{$this->step}' ";
				}
				$placeholder = $this->placeholder ?? "";
				// explicitly ensure a string is passed to htmlspecialchars to avoid
				// deprecation when null is provided on newer PHP versions
				// using htmlspecialchars here instead of coreInput::stringHtmlSafe
				// because this is for attribute handling while the latter method
				// is for in-element content
				$defaultVal = $this->default ?? '';
				$value = htmlspecialchars((string) $defaultVal, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
				$filterClass = is_string($this->filter) ? "filter_{$this->filter}" : "";
				echo "<input type='{$this->input_type}' value='{$value}' placeholder='{$placeholder}' {$minmax} {$pattern} {$step} {$this->getRenderedName()} {$this->getRenderedForm()} maxlength={$this->maxlength} minlength={$this->minlength} class='$filterClass input' {$required} type='text' id='{$this->id}' >";
				echo $this->icon_status ? $this->icon_markup : false;
			echo "</div>";
			if ($this->description) {
				echo "<p class='help'>" . $this->description . "</p>";
			}
		echo "</div>";
	}

	public function getFriendlyValue(mixed $helpful_info): mixed {
		$output = $this->default;
		$output = htmlspecialchars_decode($output); //due to some old junky filters, stuff was stored encoded, so decode it first

		if($helpful_info && $helpful_info->return_in_text_html_form==true) {
			return coreInput::stringHtmlSafe($output);
		} else {
			return $output;
		}
	}

	public function loadFromConfig(mixed $config): self {
		parent::loadFromConfig($config);
		
		$this->input_type = $config->input_type ?? 'text';
		$this->pattern = $config->pattern ?? '';
		if ($this->input_type=='range') {
			$this->min = $config->min ?? "0";
			$this->max = $config->max ?? "100";
		} elseif ($this->input_type=='number') {
			$this->min = $config->min ?? "";
			$this->max = $config->max ?? "";
		}
		$this->attribute_list = $config->attribute_list ?? "";
		$this->step = $config->step ?? null;
		$this->icon_status = $config->icon_status ?? false;
		$this->icon_parent_class = $config->icon_parent_class ?? "";
		$this->icon_markup = $config->icon_markup ?? "";

		return $this;
	}

	public function validate(): bool {
		// TODO: enhance validation
		if ($this->isMissing() || mb_strlen($this->default)>$this->maxlength) {
			return false;
		}
		return true;
	}
}
