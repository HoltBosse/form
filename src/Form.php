<?php
namespace HoltBosse\Form;

Use stdClass;
Use Exception;
Use JsonSerializable;
Use HoltBosse\Form\Input;

class Form implements JsonSerializable {
	public $id;
	public $displayName;
	public $fields;
	public $json;
	public $repeatable;
	public $formPath;

	private static $fieldRegistry = [];

	function __construct($path, $repeatable=false) {
		$this->fields = [];
		$this->repeatable = $repeatable;
		$this->formPath = $path;
		$this->loadJson($path);
	}

	private function loadJson($path) {
		if (gettype($path)=="object") {
			$obj = $path;
		} elseif (is_file($path)) {
			$this->json = file_get_contents($path);
			$obj = json_decode($this->json);
		} else {
			throw new Exception("File '{$path}' not found or invalid data passed");
		}

		if ($obj) {
			$tempfields = $obj->fields;
			$this->id = $obj->id;
			$this->displayName = isset($obj->display_name) ? $obj->display_name : $this->id;
			
			foreach ($tempfields as $field_config) {
				if(!self::$fieldRegistry[$field_config->type]) {
					throw new Exception("Field type '{$field_config->type}' not registered");
				}

				$thisfield = new self::$fieldRegistry[$field_config->type]();
				$thisfield->loadFromConfig($field_config);
				if ($this->repeatable) {
					$thisfield->in_repeatable_form = true;
				}
				if (property_exists($field_config,'name')) {
					// not all form fields require name property - only saveable items
					// HTML field / Tab field etc are for rendering only
					$this->fields[$field_config->name] = $thisfield;
				}
				else {
					$this->fields[] = $thisfield;
				}
			}
		}
	}

	/**
		* register a classname by its type string
	*/
	public static function registerField($type, $class) {
		self::$fieldRegistry[$type] = $class;
	}

	/**
		* register an type aliased to another (existing!) type
	*/
	public static function registerFieldAlias($aliasType, $type) {
		if(!self::$fieldRegistry[$type]) {
			throw new Exception("Field type '{$type}' not registered when trying to register alias '{$aliasType}'");
		}

		self::$fieldRegistry[$aliasType] = self::$fieldRegistry[$type];
	}

	public static function getFieldClass($type) {
		return self::$fieldRegistry[$type];
	}

	public function setFieldRequiredBasedOnLogic($field) {
		// logic here mirrors that of js section in 'display' function in this class
		// algorithm is essentially:
		// loop over logic arrays
		// outarray is ORs
		// each inner array is ANDs
		// note result of each set of AND checks - set a true/false accordingly in an array of OR results
		// check if any OR result is true, if so, we are required
		if ($field->required) {
			$logic = $field->logic ?? false;
			$new_required = false;
			if ($logic) {
				$or_arr = []; // array of AND test results - if ANY are true, we are required
				foreach ($logic as $or) {
					$and_arr = [];
					foreach ($or as $and) {
						// check logic
						$logic_target_field = $this->getFieldByName($and->field);
						$logic_target_value = $logic_target_field->default; // already set by set_from_submit loop in form class
						switch($and->test) {
							case '==':
								$and_arr[] = $logic_target_value == $and->value;
								break;
							default:
								// unknown test
								$and_arr[] = false;
								break;
						}
					}
					$or_arr[] = !in_array(false, $and_arr, true); // if false is in our AND array, set this OR to false;
				}
				$field->logic_checks_done = true;
				$field->required = in_array(true, $or_arr, true); // if true is anywhere in our or_arr, we're required
				if (!$field->required) {
					$field->required_ignore_by_logic = true;
				}
			}
			// else no logic available, carry on
		}
		// else - we weren't required in the first place!
	}

	public function setFromSubmit() {
		foreach ($this->fields as $field) {
			$field->setFromSubmit();
		}
		// have all field values do 'required' logic
		foreach ($this->fields as $field) {
			$this->setFieldRequiredBasedOnLogic($field);
		}
	}

	public function fieldExists($fieldName) {
		return isset($this->fields[$fieldName]);
	}

	public function getFieldByName($fieldName) {
		if (isset($this->fields[$fieldName])) {
			return $this->fields[$fieldName];
		} else {
			throw new Exception('Unable to load form field ' . $fieldName);
		}
	}

	public function isSubmitted() {
		if ($this->id) {
			$formName = Input::getVar("form_" . $this->id, "TEXT");
			if ($formName) {
				return true;
			}
		}
		return false;
	}

	public function validate() {
		foreach ($this->fields as $field) {
			if (!$field->validate()) {
				$field_info = print_r($field,true);
				return false;
			}
		}
		return true;
	}

	public function jsonSerialize(): mixed {
		$name_value_pairs = [];
		foreach ($this->fields as $field) {
			$pair = new stdClass();
			$pair->name = $field->name;
			if ($field->type=="Repeatable") {
				// loop through each repeatable form and each field inside each form
				// creating tuples for each
				$sub_form_value_array=[];
				foreach ($field->forms as $sub_form) {
					$sub_pair = new stdClass();
					$sub_pair->name = $sub_form->id;
					$sub_values = [];
					foreach ($sub_form->fields as $sub_form_field) {
						$sub_field_pair = new stdClass();
						$sub_field_pair->name = $sub_form_field->name;
						$sub_field_pair->value = $sub_form_field->default;
						$sub_values[] = $sub_field_pair;
					}
					$sub_pair->value = $sub_values;
					$sub_form_value_array[] = $sub_pair;
				}
				$pair->value = $sub_form_value_array;
			}
			else {
				$pair->value = $field->default;
			}
			if($field->save!==false) {
				$name_value_pairs[] = $pair;
			}
		}
		return $name_value_pairs;
	}

	public function serializeJson() {
		return json_encode($this);
	}

	public function deserializeJson($json) {
		$json_obj = json_decode($json);
		if ($json_obj) {
			foreach ($json_obj as $option) {
				/*
					this is a legacy check that exists due to:
					1. the page form having fields without save
					2. serialization logic not handling save field
					3. serialization handling of fields without names
				*/
				if ($option->name!=='error!!!' && $this->fieldExists($option->name)) {
					$field = $this->getFieldByName($option->name); 
					if (is_object($field)) {
						$field->default = $option->value;
					}
				} else {
					// keep going - other fields exist maybe :)
					continue;
				}
			}
		}
	}

	public static function createEmailHtmlWrapper($body, $bannerImage) {
		ob_start();
		?>
			<div style='font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 16px; padding: 0; margin: 0;'>
			<table style="padding: 0; margin: 0; border-spacing: 0px; background-color: lightgrey;">
				<tbody>
					<tr style="height: 25px;"></tr>
					<tr>
						<td style="width: 50px;"></th>
						<td style="width: 500px; padding: 25px; background-color: white; border-radius: 0px;">
							<br>
							<div style="text-align: center;">
								<a href="<?php echo "https://" . $_SERVER['SERVER_NAME']; ?>">
									<img src="<?php echo $bannerImage; ?>" alt="Logo" style="width: 300px;">
								</a>
							</div>
							<br>
							<div style="text-align: left; font-weight: normal;">
								<?php echo $body; ?>
							</div>
						</th>
						<td style="width: 50px; "></th>
					</tr>
					<tr style="height: 25px;"></tr>
				</tbody>
			</table>
		</div>    
		<?php
		return ob_get_clean();
	}

	public function createEmailHtml($bannerImage) {
		ob_start();
			echo "<h1 style='text-align: center;  font-size: 24px;'>$this->displayName submission</h1>";
			foreach($this->fields as $field) {
				if($field->name!="error!!!" && $field->save!==false) {
					echo "<p>" . $field->label . ": " . $field->default . "<p>";
				}
			}
		return $this->createEmailHtmlWrapper(ob_get_clean(), $bannerImage);
	}

	public function display($repeatableTemplate=false) {
		
		// first make sure array added to name if required
		$aftername='';
		if ($this->repeatable) {
			$aftername="[]";
		}

		// todo: move to admin template?
		echo "<style>.logic_hide {display:none;}</style>";

		echo "<div class='form_contain' id='" . $this->id . "'>";

			// loop through fields and call display();
			foreach ($this->fields as $field) {
				$nowrap_bool = $field->nowrap ?? false;
				if (!property_exists($field,'nowrap') || !$nowrap_bool) {
					// wrapped field
					// prepare logic data attribute
					$logic = $field->logic;
					if ($logic) {
						$logic_json = json_encode($logic);
					}
					else {
						$logic_json = "";
					}
					$wrapclass = $field->wrapclass ?? "";
					if ($logic) {
						$wrapclass .= " haslogic";
					}
					// prepare required data attribute
					// (remember if element is required or not)
					$req = $field->required ?? false;
					$req_data = "";
					if ($req) {
						$req_data = " data-required='true' ";
					}
					echo "<div data-field_id='{$field->id}' data-logic='{$logic_json}' $req_data class='{$wrapclass} form_field field field_id_{$field->id}'>";
				}
				$field->display($repeatableTemplate); // pass repeatableTemplate so it knows this is called for making js repeatable template
				if (!property_exists($field,'nowrap') || !$nowrap_bool) {
					echo "</div><!-- end field -->";
				}
			}
			echo "<input type='hidden' value='1' name='form_" . $this->id . "{$aftername}'>";
		
		echo "</div>";

		$jsSafeVariableId = preg_replace("/[^a-zA-Z_$]|[^\\w$]/", "_safety_", $this->id);

		// add logic js
		?>
			<script>
				if(!window.evaluateFieldLogic) {
					function evaluateFieldLogic(form, logic, element) {
						return logic.some(andConditions => 
							andConditions.every(condition => 
								evaluateFieldCondition(form, condition, element)
							)
						);
					}

					function evaluateFieldCondition(form, condition, element) {
						const { field, test, value } = condition;
						let sectionRoot = form;
						let name = field;

						//if we are in a repeatable, then adjust root and fieldname
						if(element.closest(".repeatable")) {
							sectionRoot = element.closest(".repeatable");
							name = `${field}[]`;
						}

						// get first un-ignored named field - primarily used to ignore checkbox default hidden values
						const target = sectionRoot.querySelector(`[name="${name}"]:not([data-logicignore])`); 
						let targetValue = target.value;

						if (target.nodeName=='INPUT' && target.type=='checkbox') {
							targetValue = target.checked ? 1 : 0;
						}

						switch (test) {
							case '==':
								return targetValue == value;
							case '===':
								return targetValue === value;
							case '!=':
								return targetValue != value;
							case '!==':
								return targetValue !== value;
							case '>':
								return targetValue > value;
							case '>=':
								return targetValue >= value;
							case '<':
								return targetValue < value;
							case '<=':
								return targetValue <= value;
							default:
								throw new Error(`Unsupported test: ${test}`);
						}
					}

					function updateAllFieldLogic(form) {
						form.querySelectorAll(`[data-logic]:not([data-logic=""]`).forEach(el=>{
							/* console.log(el);
							console.log(el.querySelector("label").innerText);
							console.log(evaluateFieldLogic(form, JSON.parse(el.dataset.logic), el)===true ? "true" : "false"); */

							const isRequired = el.dataset.required=='true' ? true : false;
							const actualNamedEl = el.querySelector(`#${el.dataset.field_id}`);

							if(evaluateFieldLogic(form, JSON.parse(el.dataset.logic), el)) {
								actualNamedEl.required = isRequired;
								el.classList.remove("logic_hide");
							} else {
								actualNamedEl.required = false;
								el.classList.add("logic_hide");
							}
						});
					}
				}

				if(typeof formEl_<?php echo $jsSafeVariableId; ?> === 'undefined') {
					const formEl_<?php echo $jsSafeVariableId; ?> = document.getElementById('<?php echo $this->id ?>'); //wrapping form

					formEl_<?php echo $jsSafeVariableId; ?>.addEventListener('input', (e)=>{
						updateAllFieldLogic(formEl_<?php echo $jsSafeVariableId; ?>); //run when a form element changes value
					});
					formEl_<?php echo $jsSafeVariableId; ?>.addEventListener('change', (e)=>{ //a normal select does an input+change event. a slimselect only does a change
						updateAllFieldLogic(formEl_<?php echo $jsSafeVariableId; ?>); //run when a form element changes value
					});

					updateAllFieldLogic(formEl_<?php echo $jsSafeVariableId; ?>); //run on init
				}
			</script>
		<?php
	}
}