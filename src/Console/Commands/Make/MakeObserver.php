<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ObserverMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeObserver extends ObserverMakeCommand
{
	use ModularizeGeneratorCommand;
}
