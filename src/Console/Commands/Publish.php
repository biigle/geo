<?php

namespace Biigle\Modules\Geo\Console\Commands;

use Biigle\Modules\Geo\GeoServiceProvider as ServiceProvider;
use Illuminate\Console\Command;

class Publish extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'geo:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish or refresh the public assets of this package';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => ServiceProvider::class,
            '--tag' => ['public'],
            '--force' => true,
        ]);
    }
}
