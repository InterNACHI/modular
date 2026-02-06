<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\NotificationMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeNotification extends NotificationMakeCommand
{
	use ModularizeGeneratorCommand;
}
