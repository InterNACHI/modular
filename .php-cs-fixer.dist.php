<?php

require_once __DIR__.'/vendor/autoload.php';

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRules([
		'@PSR2' => true,
		'function_declaration' => [
			'closure_function_spacing' => 'none',
		],
		'ordered_imports' => [
			'sort_algorithm' => 'alpha',
		],
		'array_indentation' => true,
		'braces' => [
			'allow_single_line_closure' => true,
		],
		'no_break_comment' => false,
		'return_type_declaration' => [
			'space_before' => 'none',
		],
		'blank_line_after_opening_tag' => true,
		'compact_nullable_typehint' => true,
		'cast_spaces' => true,
		'concat_space' => [
			'spacing' => 'none',
		],
		'declare_equal_normalize' => [
			'space' => 'none',
		],
		'function_typehint_space' => true,
		'new_with_braces' => true,
		'method_argument_space' => true,
		'no_empty_statement' => true,
		'no_empty_comment' => true,
		'no_empty_phpdoc' => true,
		'no_extra_blank_lines' => [
			'tokens' => [
				'extra',
				'use',
				'use_trait',
				'return',
			],
		],
		'no_leading_import_slash' => true,
		'no_leading_namespace_whitespace' => true,
		'no_blank_lines_after_class_opening' => true,
		'no_blank_lines_after_phpdoc' => true,
		'no_whitespace_in_blank_line' => false,
		'no_whitespace_before_comma_in_array' => true,
		'no_useless_else' => true,
		'no_useless_return' => true,
		'single_trait_insert_per_statement' => true,
		'psr_autoloading' => true,
		'dir_constant' => true,
		'single_line_comment_style' => [
			'comment_types' => ['hash'],
		],
		'include' => true,
		'is_null' => true,
		'linebreak_after_opening_tag' => true,
		'lowercase_cast' => true,
		'lowercase_static_reference' => true,
		'magic_constant_casing' => true,
		'magic_method_casing' => true,
		'class_attributes_separation' => [
			// TODO: This can be reverted when https://github.com/FriendsOfPHP/PHP-CS-Fixer/pull/5869 is merged
			'elements' => ['const' => 'one', 'method' => 'one', 'property' => 'one'],
		],
		'modernize_types_casting' => true,
		'native_function_casing' => true,
		'native_function_type_declaration_casing' => true,
		'no_alias_functions' => true,
		'no_multiline_whitespace_around_double_arrow' => true,
		'multiline_whitespace_before_semicolons' => true,
		'no_short_bool_cast' => true,
		'no_unused_imports' => true,
		'no_php4_constructor' => true,
		'no_singleline_whitespace_before_semicolons' => true,
		'no_spaces_around_offset' => true,
		'no_trailing_comma_in_list_call' => true,
		'no_trailing_comma_in_singleline_array' => true,
		'normalize_index_brace' => true,
		'object_operator_without_whitespace' => true,
		'phpdoc_annotation_without_dot' => true,
		'phpdoc_indent' => true,
		'phpdoc_no_package' => true,
		'phpdoc_no_access' => true,
		'phpdoc_no_useless_inheritdoc' => true,
		'phpdoc_single_line_var_spacing' => true,
		'phpdoc_trim' => true,
		'phpdoc_types' => true,
		'semicolon_after_instruction' => true,
		'array_syntax' => [
			'syntax' => 'short',
		],
		'list_syntax' => [
			'syntax' => 'short',
		],
		'short_scalar_cast' => true,
		'single_blank_line_before_namespace' => true,
		'single_quote' => true,
		'standardize_not_equals' => true,
		'ternary_operator_spaces' => true,
		'whitespace_after_comma_in_array' => true,
		'not_operator_with_successor_space' => true,
		'trailing_comma_in_multiline' => true,
		'trim_array_spaces' => true,
		'binary_operator_spaces' => true,
		'unary_operator_spaces' => true,
		'php_unit_method_casing' => [
			'case' => 'snake_case',
		],
		'php_unit_test_annotation' => [
			'style' => 'prefix',
		],
	])
	->setFinder(
		PhpCsFixer\Finder::create()
			->exclude('.circleci')
			->exclude('bin')
			->exclude('node_modules')
			->exclude('vendor')
			->notPath('.phpstorm.meta.php')
			->notPath('_ide_helper.php')
			->notPath('artisan')
			->in(__DIR__)
	);
