<?php

namespace Modules\TestModule\Listeners;

use Modules\TestModule\Events\TestEvent;

class TestEventListener
{
	public function handle(TestEvent $event): void
	{
	}
}
