<?php
namespace HoltBosse\Form\Fields\Honeypot;

Use HoltBosse\Form\Field;

class Honeypot extends Field {

	public $html;
	public $save;
	public $maxlength;
	public $autocomplete;

	public function display() {
		// autocomplete attribute set to nonsense which is calculated to be same as 'off' without being explicit
		// tabindex is required as -1 for accessibility reasons, so might tip off some bots, but can't hurt screen readers etc
		// set value to be ' ' (space) - allows us to use 'required' client-side, but whitespace might be more enticing to replace 
		// set display/style attributes via js as another layer of obfuscation
		// position: take up no space in document flow
		// clipPath: sneaky invisible method that might hide from most bots
		?>
		<input required placeholder="Important information" type='text' tabindex="-1" autocomplete="<?php echo $this->autocomplete;?>" id='<?php echo $this->id;?>' <?php echo $this->get_rendered_name();?> <?php echo $this->get_rendered_form(); ?> value=' '/>
		<script>
			let hp = document.getElementById('<?php echo $this->id;?>') ?? null;
			if (hp) {
				hp.style.position = 'absolute'; 
				hp.style.clipPath = 'circle(0)'; 
			}
		</script>
		<?php
	}

	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? 'STRING';
		$this->default = $config->default ?? $this->default;
		$this->autocomplete = $config->autocomplete ?? "nothingtoseehere";
	}

	public function validate() {
		if ($this->default!==" ") {
			return false;
		} else {
			return true;
		}
	}
}