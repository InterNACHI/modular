<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\Events\TestEvent;
use Modules\TestModule\Listeners\TestEventListener;

class EventsPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	protected function defineEnvironment($app): void
	{
		config()->set('app-modules.should_discover_events', true);
	}
	
	public function test_event_can_be_dispatched_and_listener_is_called(): void
	{
		$message = Str::random();
		
		Event::dispatch(new TestEvent($message));
		
		$this->assertEquals($message, TestEventListener::$last_message);
	}
}
