<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ExceptionMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeException extends ExceptionMakeCommand
{
	use ModularizeGeneratorCommand;
}
