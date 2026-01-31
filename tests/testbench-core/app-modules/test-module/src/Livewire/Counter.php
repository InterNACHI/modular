<?php

namespace Modules\TestModule\Livewire;

use Livewire\Component;

class Counter extends Component
{
	public int $count = 0;

	public function increment(): void
	{
		$this->count++;
	}

	public function render()
	{
		return view('test-module::livewire.counter');
	}
}
