<?php
namespace HoltBosse\Form\Fields\Select;

Use HoltBosse\Form\{Field, FormBuilderAttribute, FormBuilderDataType};
Use HoltBosse\Form\Input;
use Respect\Validation\Validator as v;

class Select extends Field {
	#[FormBuilderAttribute(fieldType: "SubForm", dataType: FormBuilderDataType::SelectOptions, required: true, config: ["form_base_path" => __DIR__, "form_path" => "/select_options.json"])]
	public $select_options;
	public $config;
	public $slimselect;
	public $multiple;
	public $placeholder;
	public $slimselect_ajax;
	public $slimselect_ajax_minchar;
	public $slimselect_ajax_maxchar;
	public $slimselect_ajax_url;
	public $slimselect_settings;
	public $empty_string;

	public function display() {
		$required="";
		if ($this->required) {$required=" required ";}
		$hidden = "";
		if (property_exists($this,'attribute_list')) {
			$attributes = implode(' ',$this->attribute_list);
			if (in_array('hidden',$this->attribute_list)) {
				$hidden = "hidden";
			}
		}
		echo "<div class='field {$hidden} {$required}'>";
			echo "<label class='label'>" . $this->label . "</label>";
			echo "<div class='control'>";
				echo "<div class='" . ($this->slimselect ? "slimselect_select" : ($this->multiple ? " is-multiple select" : " select")) . "'>";
					echo "<select {$required} id='{$this->id}' {$this->getRenderedName($this->multiple)} {$this->getRenderedForm()} " . 'data-repeatableindex="{{replace_with_index}}"' . ($this->multiple ? "multiple" : false) . ">";
						if ($this->required || $this->placeholder) {
							$placeholder = $this->placeholder ?? $this->label;
							echo "<option value='' >{$placeholder}</option>";
						}
						elseif ($this->empty_string) {
							// not required, but we need a 0 value top option to signify nothing
							echo "<option value='0' >{$this->empty_string}</option>";
						}
						foreach ($this->select_options as $select_option) {
							$disabled = $select_option->disabled ?? false ? " disabled " : "";
							/** @var object{text: mixed, value: mixed} $select_option */
							$selected = "";
							if ($this->multiple && $this->default != "" && in_array($select_option->value, json_decode($this->default))) {
								$selected="selected";
							/*
								this is due to legacy, and how types in php are handled.
								we get for example number values as both strings and ints, so cant use === operator here
								thus we have the issue where if we have a select item with a value of 0 it will equal null in php
								thus we add an additional check for this
							*/
							} elseif ($select_option->value == $this->default && !($select_option->value==0 && $this->default===null)) {
								$selected="selected";
							}
							echo "<option {$disabled} {$selected} value='{$select_option->value}'>" . Input::stringHtmlSafe($select_option->text) . "</option>";
						}
						if ($this->slimselect_ajax) {
							if($this->multiple && $this->default && $this->default != "" && $this->default != '[""]') {
								foreach(json_decode($this->default) as $item) {
									echo "<option selected value='$item'>$item</option>";
								}
							} elseif(!$this->multiple && $this->default && $this->default != "") {
								echo "<option selected value='$this->default'>$this->default</option>";
							}
						}
					echo "</select>";
				echo "</div>";
			echo "</div>";
		echo "</div>";
		if ($this->description) {
			echo "<p class='help {$hidden}'>" . $this->description . "</p>";
		}
		if($this->slimselect):
		?>
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/2.12.0/slimselect.min.css"/>
			<script type="module">
				import SlimSelect from 'https://cdnjs.cloudflare.com/ajax/libs/slim-select/2.12.0/slimselect.es.js';
				console.log(`[<?php echo $this->getRenderedName(); ?>][data-repeatableindex="{{replace_with_index}}"]`);
				console.log(document.currentScript);

				document.querySelector(`[<?php echo $this->getRenderedName($this->multiple); ?>][data-repeatableindex="{{replace_with_index}}"]`).slimselect = new SlimSelect({
					select: document.querySelector(`[<?php echo $this->getRenderedName($this->multiple); ?>][data-repeatableindex="{{replace_with_index}}"]`),
					<?php if($this->slimselect_ajax): ?>
					events: {
						search: (search, currentData) => {
							return new Promise((resolve, reject) => {
								if (search.length < <?php echo $this->slimselect_ajax_minchar; ?>) {
									return reject('Please enter at least <?php echo $this->slimselect_ajax_minchar; ?> characters');
								}

								fetch('<?php echo $this->slimselect_ajax_url; ?>?searchterm=' + encodeURI(search)).then(function (response) {
									return response.json()
								}).then(function (json) {
									let data = [];
									json.data.forEach((item)=>{
										data.push({text: item.text, value: item.value})
									});

									//console.log(data);
									resolve(data);
								}).catch(function(error) {
									return reject('Error searching');
								})
							});
						},
					},
					<?php endif; ?>
					<?php if($this->slimselect_settings): ?>
					settings: <?php echo $this->slimselect_settings; ?>
					<?php endif; ?>
				});
			</script>
		<?php
		endif;
	}

	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->select_options = $config->select_options ?? [];
		$this->empty_string = $config->empty_string ?? '';
		$this->slimselect = isset($config->slimselect) ? $config->slimselect : false;
		$this->multiple = isset($config->multiple) ? $config->multiple : false;
		$this->slimselect_ajax = $config->slimselect_ajax ?? false;
		$this->slimselect_ajax_url = $config->slimselect_ajax_url ?? "";
		$this->slimselect_ajax_minchar = $config->slimselect_ajax_minchar ?? 3;
		$this->slimselect_settings = $config->slimselect_settings ?? null;
		if ($this->multiple) {
			$this->filter = $config->filter ?? V::arrayType()->each(v::stringVal());
		}
		else {
			$this->filter = $config->filter ?? v::StringVal();
		}
	}

	public function validate() {
		if ($this->isMissing()) {
			return false;
		}
		return true;
	}
}
