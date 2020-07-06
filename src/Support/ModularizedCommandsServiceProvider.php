<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Console\Application as Artisan;
use Illuminate\Support\ServiceProvider;
use InterNACHI\Modular\Console\Commands\Make\MakeChannel;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Console\Commands\Make\MakeController;
use InterNACHI\Modular\Console\Commands\Make\MakeEvent;
use InterNACHI\Modular\Console\Commands\Make\MakeException;
use InterNACHI\Modular\Console\Commands\Make\MakeFactory;
use InterNACHI\Modular\Console\Commands\Make\MakeJob;
use InterNACHI\Modular\Console\Commands\Make\MakeListener;
use InterNACHI\Modular\Console\Commands\Make\MakeMail;
use InterNACHI\Modular\Console\Commands\Make\MakeMiddleware;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Console\Commands\Make\MakeNotification;
use InterNACHI\Modular\Console\Commands\Make\MakeObserver;
use InterNACHI\Modular\Console\Commands\Make\MakePolicy;
use InterNACHI\Modular\Console\Commands\Make\MakeProvider;
use InterNACHI\Modular\Console\Commands\Make\MakeRequest;
use InterNACHI\Modular\Console\Commands\Make\MakeResource;
use InterNACHI\Modular\Console\Commands\Make\MakeRule;
use InterNACHI\Modular\Console\Commands\Make\MakeSeeder;
use InterNACHI\Modular\Console\Commands\Make\MakeTest;

class ModularizedCommandsServiceProvider extends ServiceProvider
{
	protected $overrides = [
		'command.controller.make' => MakeController::class,
		'command.console.make' => MakeCommand::class,
		'command.channel.make' => MakeChannel::class,
		'command.event.make' => MakeEvent::class,
		'command.exception.make' => MakeException::class,
		'command.factory.make' => MakeFactory::class,
		'command.job.make' => MakeJob::class,
		'command.listener.make' => MakeListener::class,
		'command.mail.make' => MakeMail::class,
		'command.middleware.make' => MakeMiddleware::class,
		'command.model.make' => MakeModel::class,
		'command.notification.make' => MakeNotification::class,
		'command.observer.make' => MakeObserver::class,
		'command.policy.make' => MakePolicy::class,
		'command.provider.make' => MakeProvider::class,
		'command.request.make' => MakeRequest::class,
		'command.resource.make' => MakeResource::class,
		'command.rule.make' => MakeRule::class,
		'command.seeder.make' => MakeSeeder::class,
		'command.test.make' => MakeTest::class,
	];
	
	public function register(): void
	{
		Artisan::starting(function() {
			foreach ($this->overrides as $alias => $class_name) {
				$this->app->singleton($alias, $class_name);
			}
			
			$this->app->singleton('command.migrate.make', function($app) {
				return new MakeMigration($app['migration.creator'], $app['composer']);
			});
		});
	}
}
