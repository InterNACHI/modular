<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\Models\TestModel;
use Modules\TestModule\Policies\TestModelPolicy;

class GatePluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_policy_is_registered_for_model(): void
	{
		$this->actingAs(new User());
		
		$this->assertInstanceOf(TestModelPolicy::class, Gate::getPolicyFor(TestModel::class));
		$this->assertTrue(Gate::allows('create', TestModel::class));
	}
	
	public function test_policy_can_be_resolved_by_model_instance(): void
	{
		$this->actingAs(new User());
		
		$model = new TestModel();
		
		$this->assertInstanceOf(TestModelPolicy::class, Gate::getPolicyFor($model));
		$this->assertFalse(Gate::allows('view', $model));
	}
}
