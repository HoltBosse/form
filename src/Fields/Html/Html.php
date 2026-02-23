<?php
namespace HoltBosse\Form\Fields\Html;

Use HoltBosse\Form\Field;

class Html extends Field {
	public mixed $html;

	public function display(): void {
		echo $this->html;
	}

	public function loadFromConfig(object $config): self {
		parent::loadFromConfig($config);
		
		$this->html = $config->html ?? "";
		$this->save = $config->save ?? false;

		return $this;
	}
}