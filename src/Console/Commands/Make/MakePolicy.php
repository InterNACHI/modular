<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\PolicyMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakePolicy extends PolicyMakeCommand
{
	use ModularizeGeneratorCommand;
}
