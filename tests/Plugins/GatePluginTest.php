<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Contracts\Auth\Access\Gate;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\Models\TestModel;
use Modules\TestModule\Policies\TestModelPolicy;

class GatePluginTest extends TestCase
{
	use PreloadsAppModules;

	public function test_policy_is_registered_for_model(): void
	{
		$gate = $this->app->make(Gate::class);

		$policy = $gate->getPolicyFor(TestModel::class);

		$this->assertInstanceOf(TestModelPolicy::class, $policy);
	}

	public function test_policy_can_be_resolved_by_model_instance(): void
	{
		$gate = $this->app->make(Gate::class);
		$model = new TestModel();

		$policy = $gate->getPolicyFor($model);

		$this->assertInstanceOf(TestModelPolicy::class, $policy);
	}
}
