<?php
return PhpCsFixer\Config::create()
	->setRules([
		'@PSR2' => true,
		'braces' => array ('position_after_functions_and_oop_constructs' => 'same'),
		'class_definition' => array ('single_line' => true),
		'elseif' => false,
		'no_break_comment' => false,
		'no_trailing_whitespace_in_comment' => false,
		'indentation_type' => true,
		'array_indentation' => true,
	])
	->setIndent("\t")
	->setLineEnding("\n");
