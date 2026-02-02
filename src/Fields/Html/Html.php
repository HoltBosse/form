<?php
namespace HoltBosse\Form\Fields\Html;

Use HoltBosse\Form\Field;

class Html extends Field {
	public $html;

	public function display() {
		echo $this->html;
	}

	public function loadFromConfig($config) {
		parent::loadFromConfig($config);
		
		$this->html = $config->html ?? "";
		$this->save = $config->save ?? false;

		return $this;
	}
}