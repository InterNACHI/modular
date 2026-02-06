<?php

namespace Modules\TestModule\Listeners;

use Modules\TestModule\Events\TestEvent;

class TestEventListener
{
	public static string $last_message = '';
	
	public function handle(TestEvent $event): void
	{
		self::$last_message = $event->message;
	}
}
