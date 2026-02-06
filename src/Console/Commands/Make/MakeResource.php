<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ResourceMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeResource extends ResourceMakeCommand
{
	use ModularizeGeneratorCommand;
}
