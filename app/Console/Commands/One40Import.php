<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class One40Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one40:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import your Twitter archive';

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
        // go to /upload
        // run import
    }
}
