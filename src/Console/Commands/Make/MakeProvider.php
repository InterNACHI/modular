<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ProviderMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeProvider extends ProviderMakeCommand
{
	use ModularizeGeneratorCommand;
}
