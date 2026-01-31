<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\JobMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeJob extends JobMakeCommand
{
	use ModularizeGeneratorCommand;
}
