<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetUpStubClassNamePrefixModule extends Migration
{
	public function up()
	{
		// Schema::create('StubModuleName', function(Blueprint $table) {
		// 	$table->id();
		// 	$table->timestamps();
		// 	$table->softDeletes();
		// });
	}
	
	public function down()
	{
		// Schema::dropIfExists('StubModuleName');
	}
}
