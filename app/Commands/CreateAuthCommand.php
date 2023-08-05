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

        foreach (['auth', 'host'] as $option) {
            if (! $this->option($option)) {
                $this->input->setOption($option, $this->ask(sprintf('ENTER %s', strtoupper(
                    $option
                ))));
            }
        }

        $config->store([
            'auth' => $this->option('auth'),
            'host' => $this->option('host'),
            'name' => $this->argument(
                'name'
            ),
        ]);

        $gphoto = new GPhoto($this->argument('name'));

        $url = $gphoto->oauth()->buildFullAuthorizationUri([
            'access_type' => 'offline',
        ]);

        $this->components->info('Auth URL : ' . $url);

        // open auth url in browser
        foreach (['xdg-open', 'sensible-browser', 'start'] as $command) {
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
            ['host', null, InputOption::VALUE_REQUIRED, 'Authorised redirect URIs'],
        ];
    }
}
