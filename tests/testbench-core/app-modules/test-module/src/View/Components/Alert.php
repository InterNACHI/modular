<?php

namespace Modules\TestModule\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
	public function __construct(
		public string $type = 'info'
	) {
	}
	
	public function render()
	{
		return view('test-module::components.alert');
	}
}
