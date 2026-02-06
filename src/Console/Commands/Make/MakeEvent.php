<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\EventMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeEvent extends EventMakeCommand
{
	use ModularizeGeneratorCommand;
}
