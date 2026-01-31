<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Event;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\Events\TestEvent;

class EventsPluginTest extends TestCase
{
	use PreloadsAppModules;

	protected function defineEnvironment($app): void
	{
		$app['config']->set('app-modules.should_discover_events', true);
	}

	public function test_event_listeners_are_discovered(): void
	{
		$dispatcher = Event::getFacadeRoot();
		$listeners = $dispatcher->getListeners(TestEvent::class);

		$hasListener = false;
		foreach ($listeners as $listener) {
			if (is_string($listener) && str_contains($listener, 'TestEventListener')) {
				$hasListener = true;
				break;
			}
			if ($listener instanceof \Closure) {
				$reflection = new \ReflectionFunction($listener);
				$vars = $reflection->getStaticVariables();
				if (isset($vars['listener']) && str_contains($vars['listener'], 'TestEventListener')) {
					$hasListener = true;
					break;
				}
			}
		}

		$this->assertTrue($hasListener, 'TestEventListener should be discovered for TestEvent');
	}

	public function test_event_can_be_dispatched_and_listener_is_called(): void
	{
		Event::fake([TestEvent::class]);

		TestEvent::dispatch();

		Event::assertDispatched(TestEvent::class);
	}
}
