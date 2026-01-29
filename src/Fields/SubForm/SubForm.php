<?php
namespace HoltBosse\Form\Fields\SubForm;

Use HoltBosse\Form\{Field, Form};
Use HoltBosse\Form\Input;
use Respect\Validation\Validator as v;

class SubForm extends Field {
	public string $form_path;
	public Form $sub_form;
	public array $forms;
	public string $form_base_path = '';

	public function generateRepeatButtons() {
		?>
			<button type='button' onclick='this.closest(".subform").remove();' class='sf_close button btn pull-right is-warning remove_repeater'>-</button>
			<button type='button' class='sf_up button btn pull-right is-info remove_repeater'>^</button>
			<button type='button' class='sf_down button btn pull-right is-info remove_repeater'>v</button>
		<?php
	}

	public function display() {
		$subFormRootClass = 'subform_field_' . uniqid();

		$saved_data = json_decode($this->default);

		// get example repeatable for js rendering
		$this->sub_form = new Form($this->form_base_path . $this->form_path, true); 
		// loop over existing data and render
		$this->forms = [];

		echo "<style>" . file_get_contents(__DIR__ . "/style.css") . "</style>";
		echo "<div class='field $subFormRootClass " . ($this->required ? "required" : "") . "'>";
			echo "<label for='{$this->id}' class='label'>";
				echo $this->label;
			echo "</label>";
			
			echo "<div class='subform_container' id='subform_container_{$this->sub_form->id}'>";
		
			if ($saved_data) {
				foreach ($saved_data as $repeatable_index=>$repeatable_form_data) {
					// load form
					$repeatable_form = new Form($this->form_base_path . $this->form_path, true); // second parameter is boolean for repeatable or not
					$repeatable_form->deserializeJson(json_encode($repeatable_form_data));
					?>
					<div class='subform'>
						<?php
							$this->generateRepeatButtons();

							ob_start();
								$repeatable_form->display();
							$rform_contents = ob_get_clean();

							$rform_contents = str_replace("{{repeatable_id_suffix}}", uniqid(), $rform_contents);
							$rform_contents = str_replace("{{replace_with_index}}", $repeatable_index, $rform_contents);

							echo $rform_contents;
						?>
					</div>
					<?php
				}
			}
		
			echo "</div>"; // end repeatable form container
			
				echo "<button type='button' data-repeatable_template_var='repeatable_form_template_{$this->sub_form->id}' id='add_repeater_{$this->sub_form->id}' class='add_new_repeatable button btn is-primary'>+</button>";

				if ($this->description) {
					echo "<p class='help'>" . $this->description . "</p>";
				}
			echo "</div>"; // end field

			// generate template for form repeatable and store in JS variable
			// render form
			$repeatable_template = '';
			ob_start(); // start new output buffer to escape any backticks / string literals inside form display - image field has LOTS
			?>
				<div class='subform'>
					<?php 
						$this->generateRepeatButtons();
						$this->sub_form->display(true);
					?>
				</div>
			<?php
			$repeatable_template = ob_get_clean();
		?>
			<script type="module">
				const repeatableMarkup = <?php echo json_encode($repeatable_template); ?>;

				const add_repeater = document.getElementById('add_repeater_<?php echo $this->sub_form->id;?>');
				add_repeater.addEventListener('click',(e)=>{
					// create new document fragment from template and add to repeater container
					let markup = repeatableMarkup;
					let repeat_count = document.getElementById('subform_container_<?php echo $this->sub_form->id;?>').querySelectorAll('div.subform').length;
					// insert index if required
					markup = markup.replaceAll(/{{replace_with_index}}/g, repeat_count.toString());
					// insert unique id if required (image / slimselect js need unique ids for script)
					let unique_id_suffix = '_' + Math.random().toString(36).substr(2, 9);
					markup = markup.replaceAll(/{{repeatable_id_suffix}}/g, unique_id_suffix);
					// create and insert node with markup
					let new_node = document.createRange().createContextualFragment(markup);
					let this_repeater = document.getElementById('subform_container_<?php echo $this->sub_form->id;?>');
					this_repeater.appendChild(new_node);

					updateAllFieldLogic(e.target.closest(".form_contain"));
				});

				document.querySelector(".<?php echo $subFormRootClass; ?>").addEventListener("click", (e)=>{
					if(e.target.classList.contains("sf_up")) {
						const el = e.target.closest(".subform");
						el.style.viewTransitionName = "woosh";
						if (el.previousElementSibling) {
							if (document.startViewTransition) {
								document.startViewTransition(() => {
									el.parentNode.insertBefore(el, el.previousElementSibling);
								});
							} else {
								el.parentNode.insertBefore(el, el.previousElementSibling);
							}
						} else {
							alert('Already at top!');
						}
					}
					else if(e.target.classList.contains("sf_down")) {
						const el = e.target.closest(".subform");
						if (el.nextElementSibling) {
							if (document.startViewTransition) {
								document.startViewTransition(() => {
									el.parentNode.insertBefore(el.nextElementSibling, el);
								});
							} else {
								el.parentNode.insertBefore(el.nextElementSibling, el);
							}
						}
						else {
							alert('Already at bottom!');
						}
					}
				});
			</script>
		<?php
	}

	public function setFromSubmit() {
		// create base repeatable form
		$forms=[];
		$repeatable_form = new Form($this->form_base_path . $this->form_path, true); // must be true / repeatable
		$form_arr = Input::getvar('form_' . $repeatable_form->id, v::ArrayType());
		if (is_array($form_arr)) {
			$repeat_count = sizeof ($form_arr);
		}
		else {
			$repeat_count=0;
		}
		// loop over this submitted repeatable and make sub-form for each element
		for ($n=0; $n<$repeat_count; $n++) {
			$repeatable_form = new Form($this->form_base_path . $this->form_path, true);
			$repeatable_form->formPath = $this->form_path;
			// get info for field
			foreach ($repeatable_form->fields as $field) {
				$field->setFromSubmitRepeatable($n);
			}
			foreach ($repeatable_form->fields as $field) {
				$repeatable_form->setFieldRequiredBasedOnLogic($field);
			}
			$forms[] = $repeatable_form;
		}
		$this->forms = $forms;

		$this->default = json_encode($forms);
	}


	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->form_path = $config->form_path ?? '';
	}

	public function validate() {
		// assume $this->forms has been set by set_from_submit
		$all_valid=true;
		foreach ($this->forms as $subform) {
			if (!$subform->validate()) {
				$all_valid=false;
			}
		}
		return $all_valid;
	}
}