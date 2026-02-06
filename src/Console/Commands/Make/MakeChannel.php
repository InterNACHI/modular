<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ChannelMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeChannel extends ChannelMakeCommand
{
	use ModularizeGeneratorCommand;
}
