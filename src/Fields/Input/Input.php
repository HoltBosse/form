<?php
namespace HoltBosse\Form\Fields\Input;

Use HoltBosse\Form\{Field, FormBuilderAttribute, FormBuilderDataType};
Use HoltBosse\Form\Input as coreInput;

class Input extends Field {

	public $select_options;
	public $pattern;
	public $input_type;
	public $min;
	public $max;
	public $attribute_list;
	public $step;
	public $icon_status;
	public $icon_parent_class;
	public $icon_markup;
	#[FormBuilderAttribute(fieldType: "Input", dataType: FormBuilderDataType::String, required: false)]
	public $placeholder; //yes this is re-declared from parent for form builder

	public function display() {
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
				//explictly using htmlspecialchars here instead of coreInput::stringHtmlSafe because this is for attribute handling while the latter method is for in elements
				//for older php versions that convert only double quotes, we want to match modern php
				$value = htmlspecialchars($this->default, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
				$filterClass = is_string($this->filter) ? "filter_{$this->filter}" : "";
				echo "<input type='{$this->input_type}' value='{$value}' placeholder='{$placeholder}' {$minmax} {$pattern} {$step} {$this->getRenderedName()} {$this->getRenderedForm()} maxlength={$this->maxlength} minlength={$this->minlength} class='$filterClass input' {$required} type='text' id='{$this->id}' >";
				echo $this->icon_status ? $this->icon_markup : false;
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
			return coreInput::stringHtmlSafe($output);
		} else {
			return $output;
		}
	}

	public function loadFromConfig($config) {
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

	public function validate() {
		// TODO: enhance validation
		if ($this->isMissing() || mb_strlen($this->default)>$this->maxlength) {
			return false;
		}
		return true;
	}
}
