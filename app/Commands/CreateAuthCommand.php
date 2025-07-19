<?php

namespace App\Commands;

use App\Config\Config;
use App\GPhoto;
use App\Listeners\TokenListener;
use Exception;
use Illuminate\Support\Facades\Process;
use JetBrains\PhpStorm\NoReturn;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputOption;

class CreateAuthCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $name = 'auth:create';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create authenticated token';

    /**
     * @throws Exception
     */
    #[NoReturn]
    public function handle(Config $config) : void
    {
        if (! $this->argument('name')) {
            $this->input->setArgument('name', 'default');
        }

        if (! $this->option('auth')) {
            $this->input->setOption('auth', $this->ask('ENTER AUTH FILE PATH'));
        }

        if (! $this->option('listen')) {
            $listen = $this->ask('ENTER LISTEN HOST AND PORT', 'localhost:3000');

            // Add http:// prefix if no scheme is provided
            if (! str_starts_with($listen, 'http://') && ! str_starts_with($listen, 'https://')) {
                $listen = 'http://' . $listen;
            }

            // Force http:// by replacing https:// if present
            $listen = preg_replace('/^https:\/\//i', 'http://', $listen);

            $this->input->setOption('listen', $listen);
        }

        if (! $this->option('redirect')) {
            $this->input->setOption('redirect', $this->ask('ENTER REDIRECT URI', $this->option('listen')));
        }

        $config->store([
            'name'     => $this->argument('name'),
            //
            'auth'     => $this->option('auth'),
            'listen'   => $this->option('listen'),
            'redirect' => $this->option('redirect'),
        ]);

        $gphoto = new GPhoto($this->argument('name'));

        $url = $gphoto->oauth()->buildFullAuthorizationUri([
            'access_type' => 'offline',
        ]);

        $this->components->info('Auth URL : ' . $url);

        // open auth url in browser
        foreach (['xdg-open', 'sensible-browser', 'start', 'open'] as $command) {
            $process = Process::run(sprintf('%s "%s"', $command, $url));

            if ($process->successful()) {
                break;
            }
        }

        $listener = new TokenListener($gphoto);
        $listener->listen($this->components);
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

    /**
     * @return array[]
     */
    public function getOptions() : array
    {
        return [
            ['auth', null, InputOption::VALUE_REQUIRED, 'Credential file'],
            ['listen', null, InputOption::VALUE_REQUIRED, 'Host and port to listen for OAuth callback (e.g., 127.0.0.1:8080)'],
            ['redirect', null, InputOption::VALUE_OPTIONAL, 'Redirect URI (default: uses the listen input value)'],
        ];
    }
}
