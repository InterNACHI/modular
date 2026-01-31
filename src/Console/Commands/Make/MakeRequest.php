<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\RequestMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeRequest extends RequestMakeCommand
{
	use ModularizeGeneratorCommand;
}
