<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\MailMakeCommand;
use InterNACHI\Modularize\ModularizeGeneratorCommand;

class MakeMail extends MailMakeCommand
{
	use ModularizeGeneratorCommand;
}
