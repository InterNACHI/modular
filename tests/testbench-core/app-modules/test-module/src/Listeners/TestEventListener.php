<?php

namespace Modules\TestModule\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\TestModule\Events\TestEvent;

class TestEventListener
{
    public function handle(TestEvent $event): void
    {
        //
    }
}
