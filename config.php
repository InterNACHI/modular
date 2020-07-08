<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Modules Namespace
	|--------------------------------------------------------------------------
	|
	| This is the PHP namespace that your modules will be created in. For
	| example, a module called "Helpers" will be placed in \Modules\Helpers
	| by default.
	|
	| It his *highly recommended* that you configure this to your organization
	| name to make extracting modules to their own package easier (should you
	| choose to ever do so).
	|
	| If you set the namespace, you should also set the vendor name to match.
	|
	*/
	
	'modules_namespace' => 'Modules',
	
	/*
	|--------------------------------------------------------------------------
	| Composer "Vendor" Name
	|--------------------------------------------------------------------------
	|
	| This is the prefix used for your composer.json file. This should be the
	| kebab-case version of your module namespace (if left null, we will
	| generate the kebab-case version for you).
	|
	*/
	
	'modules_vendor' => null,
	
	/*
	|--------------------------------------------------------------------------
	| Modules Directory
	|--------------------------------------------------------------------------
	|
	| If you want to install modules in a custom directory, you can do so here.
	| Keeping the default `app-modules/` directory is highly recommended,
	| though, as it keeps your modules near the rest of your application code
	| in an alpha-sorted directory listing. 
	|
	*/
	
	'modules_directory' => 'app-modules',
	
	/*
	|--------------------------------------------------------------------------
	| Base Test Case
	|--------------------------------------------------------------------------
	|
	| This is the base TestCase class name that auto-generated Tests should
	| extend. By default it assumes the default \Tests\TestCase exists.
	|
	*/
	
	'tests_base' => 'Tests\TestCase',
];
