<?php
namespace HoltBosse\Form;

use \Attribute;
use \InvalidArgumentException;

#[Attribute]
class FormBuilderAttribute {
	public string $fieldType;
	public FormBuilderDataType $dataType;
	public bool $required;
	public ?string $label;
	public ?string $description;

	public function __construct(string $fieldType, FormBuilderDataType $dataType, bool $required, ?string $label = null, ?string $description = null) {
		if (!is_subclass_of(Form::getFieldClass($fieldType), Field::class) && $fieldType !== Field::class) {
			throw new InvalidArgumentException(
				sprintf('fieldType must be Field or subclass, %s given', $fieldType)
			);
		}

		$this->fieldType = $fieldType;
		$this->dataType = $dataType;
		$this->required = $required;
		$this->label = $label;
		$this->description = $description;
	}
}