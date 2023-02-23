<?php

namespace App\Commands;

use App\GPhoto;
use App\Listeners\TokenListener;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;
use Octopy\Inotify\Exceptions\MissingSourceException;
use Symfony\Component\Console\Input\InputOption;

class ReloadAuthCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $name = 'auth:reload';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Reload authenticated token ';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws MissingSourceException
     */
    public function handle() : void
    {
        if (! $this->argument('name')) {
            $this->input->setArgument('name', $this->ask('ENTER NAME'));
        }

        $gphoto = new GPhoto($this->argument('name'));

        if (! file_exists($gphoto->path('config.json'))) {
            $this->components->warn(sprintf(
                'CONFIG NOT EXISTS run "gphoto auth:create %s"', $this->argument('name')
            ));

            return;
        }

        // open auth url in browser
        foreach (['xdg-open', 'sensible-browser', 'start'] as $command) {
            $process = Process::run(sprintf('%s "%s"', $command, $gphoto->oauth()->buildFullAuthorizationUri([
                'access_type' => 'offline',
            ])));

            if ($process->successful()) {
                break;
            }
        }

        $listener = new TokenListener($gphoto);
        $listener->listen(
            $this->components
        );
    }

    /**
     * @return array[]
     */
    public function getArguments() : array
    {
        return [
            ['name', InputOption::VALUE_REQUIRED, 'Authentication name'],
        ];
    }
}

