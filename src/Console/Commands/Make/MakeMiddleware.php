<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Routing\Console\MiddlewareMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeMiddleware extends MiddlewareMakeCommand
{
	use ModularizeGeneratorCommand;
}
