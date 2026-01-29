<?php
namespace HoltBosse\Form;

enum FormBuilderDataType: string {
	case String = 'STRING';
	case LetterString = 'LETTERSTRING';
	case Integer = 'INTEGER';
	case Bool = 'BOOL'; 
	case SelectOptions = 'SELECTOPTIONS';
}