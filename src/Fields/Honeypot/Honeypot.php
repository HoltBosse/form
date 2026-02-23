<?php
namespace HoltBosse\Form\Fields\Honeypot;

Use HoltBosse\Form\Field;
use Respect\Validation\Validator as v;

class Honeypot extends Field {
	public bool $save = false;
	public ?string $autocomplete = null;

	public function display(): void {
		// autocomplete attribute set to nonsense which is calculated to be same as 'off' without being explicit
		// tabindex is required as -1 for accessibility reasons, so might tip off some bots, but can't hurt screen readers etc
		// set value to be ' ' (space) - allows us to use 'required' client-side, but whitespace might be more enticing to replace 
		// set display/style attributes via js as another layer of obfuscation
		// position: take up no space in document flow
		// clipPath: sneaky invisible method that might hide from most bots
		?>
		<input required placeholder="Important information" type='text' tabindex="-1" autocomplete="<?php echo $this->autocomplete;?>" id='<?php echo $this->id;?>' <?php echo $this->getRenderedName();?> <?php echo $this->getRenderedForm(); ?> value=' '/>
		<script>
			let hp = document.getElementById('<?php echo $this->id;?>') ?? null;
			if (hp) {
				hp.style.position = 'absolute'; 
				hp.style.clipPath = 'circle(0)'; 
			}
		</script>
		<?php
	}

	public function loadFromConfig(object $config): self {
		parent::loadFromConfig($config);
		
		$this->filter = $config->filter ?? v::StringVal();
		$this->default = $config->default ?? $this->default;
		$this->autocomplete = $config->autocomplete ?? "nothingtoseehere";
		$this->save = $config->save ?? false;

		return $this;
	}

	public function validate(): bool {
		if ($this->default!==" ") {
			return false;
		} else {
			return true;
		}
	}
}