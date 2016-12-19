<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Setup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one40:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup your one40 installation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$this->setupDatabase();

	    if (User::first()) {
		    $this->warn('User account already created, aborting.');
		    return;
	    }

	    if (! $user = $this->createUser()) {
	    	$this->info('Error: User creation failed. Please try again');
	    	return;
	    }

	    $this->info('Setup successfully completed!');
    }

	private function setupDatabase()
	{
		if (Schema::hasTable('migrations'))
		{
			$this->warn('`migrate:install` already run, skipping');
		} else {
			$this->info('Running migration install');
			Artisan::call('migrate:install');
		}

		if (Schema::hasTable('tweets'))
		{
			$this->warn('`migrate` already run, skipping');
		} else {
			$this->info('Running table migrations');
			Artisan::call('migrate', ['--force' => true]);
		}
	}

	private function createUser()
	{
		$this->info('Creating user account');

		$email = $this->ask('Email Address');

		try
		{
			Validator::make(['email' => $email], ['email' => 'email'])->validate();
		} catch (ValidationException $e) {
			$this->info('Error: Invalid email. aborting.');
			return false;
		}

		$password = $this->secret('Password');
		$confirmPassword = $this->secret('Confirm Password');

		if ($password != $confirmPassword)
		{
			$this->info('Error: Passwords do not match. aborting.');
			return false;
		}

		return User::create([
			'email' => $email,
			'password' => $password
		]);
	}
}
