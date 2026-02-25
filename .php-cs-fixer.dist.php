<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
	->setRiskyAllowed(false)
	->setRules([
		'constant_case' => ['case' => 'upper'],
		'braces_position' => [
			'classes_opening_brace' => 'same_line',
			'functions_opening_brace' => 'same_line',
			'anonymous_functions_opening_brace' => 'same_line',
			'control_structures_opening_brace' => 'same_line',
			'anonymous_classes_opening_brace' => 'same_line',
		],
	])
	->setFinder($finder);