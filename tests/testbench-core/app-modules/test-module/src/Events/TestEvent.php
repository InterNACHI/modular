<?php

namespace Modules\TestModule\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent
{
	use Dispatchable;
	use InteractsWithSockets;
	use SerializesModels;
	
	public function __construct(
		public string $message = '',
	) {
	}
	
	public function __invoke()
	{
		$this->message .= ' (invoked)';
	}
}
