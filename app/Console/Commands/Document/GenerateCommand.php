<?php

namespace App\Console\Commands\Document;

use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Input version in format: V1, or V2,..
     *
     * @var string
     */
    protected $signature = 'swagger:generate {--ver}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';

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
     * @return int
     */
    public function handle()
    {
        $version = lcfirst($this->option('ver'));
        $generator = resolve(GeneratorFactory::class)->initialize();

        if ($version) {
            $this->info("Generating docs $version...");
            $generator->generate($version);

            return 0;
        }

        $this->info("Generating docs...");

        $generator->generate();

        $this->info('Generating finish!!!');
        return 0;
    }
}
