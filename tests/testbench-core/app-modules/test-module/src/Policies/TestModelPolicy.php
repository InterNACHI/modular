<?php

namespace Modules\TestModule\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\TestModule\Models\TestModel;

class TestModelPolicy
{
	use HandlesAuthorization;

	public function view($user, TestModel $model): bool
	{
		return true;
	}

	public function create($user): bool
	{
		return true;
	}
}
