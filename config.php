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
	| It is *highly recommended* that you configure this to your organization
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
	| This is the prefix used for your composer.json file. This is usually the
	| kebab-case version of your module namespace (if left null, we will
	| generate it for you using the `name_case` preference below).
	|
	*/
	
	'modules_vendor' => null,
	
	/*
	|--------------------------------------------------------------------------
	| Module Name Casing
	|--------------------------------------------------------------------------
	|
	| By default, Modular uses kebab-case for module names. For example, a 
	| module "shopping cart" module would be named "shopping-cart". If you
	| prefer another style of casing, you can set that here.
	|
	| Options: "kebab", "snake", "camel", "studly"
	|
	*/
	
	'name_case' => 'kebab',
	
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
	
	/*
	|--------------------------------------------------------------------------
	| Custom Stubs
	|--------------------------------------------------------------------------
	|
	| If you would like to use your own custom stubs for new modules, you can
	| configure those here. This should be an array where the key is the path
	| relative to the module and the value is the absolute path to the stub
	| stub file. Destination paths and contents support placeholders. See the
	| README.md file for more information.
	|
	| For example:
	|
	| 'stubs' => [
	| 	'src/Providers/StubClassNamePrefixServiceProvider.php' => base_path('stubs/app-modules/ServiceProvider.php'),
	| ],
	*/
	
	'stubs' => null,
];
