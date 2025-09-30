<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshAndSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations, seeders, passport installation, and storage linking in one command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting the refresh and seed process...');

        // Run migrate:fresh
        $this->call('migrate:fresh');

        // Run db:seed
        $this->call('db:seed');

        // Run db:seed for AdminSeeder
        $this->call('db:seed', ['--class' => 'AdminSeeder']);

        // Run passport:install
        $this->call('passport:install', ['--force' => true]);

        // Run storage:link
        $this->call('storage:link', ['--force' => true]);

        $this->info('All tasks completed successfully!');

        return Command::SUCCESS;
    }
}
