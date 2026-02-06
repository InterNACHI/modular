<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\CastMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeCast extends CastMakeCommand
{
	use ModularizeGeneratorCommand;
}
