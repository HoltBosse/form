<?php
namespace HoltBosse\Form\Fields\Html;

Use HoltBosse\Form\Field;

class Html extends Field {
	public string $html;

	public function display(): void {
		echo $this->html;
	}

	public function loadFromConfig(object $config): void {
		parent::loadFromConfig($config);
		
		$this->html = $config->html ?? "";
	}
}