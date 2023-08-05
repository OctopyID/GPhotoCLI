<?php

namespace App\Listeners;

use App\GPhoto;
use Illuminate\Console\View\Components\Factory as Output;
use Illuminate\Support\Facades\Process;
use Octopy\Inotify\Exceptions\MissingSourceException;
use Octopy\Inotify\Inotify;
use Octopy\Inotify\Watcher\InotifyWatcher;
use function file_put_contents;

class TokenListener
{
    /**
     * @param  GPhoto $gphoto
     */
    public function __construct(protected GPhoto $gphoto)
    {
        #
    }

    /**
     * @param  Output $output
     * @return void
     * @throws MissingSourceException
     */
    public function listen(Output $output) : void
    {
        if ($endpoint = $this->getTokenListenerEndPoint()) {
            # check if port is available
            $socket = @fsockopen($this->getHostAndPort());
            if (is_resource($socket)) {
                $output->error(sprintf('PORT %s IS ALREADY IN USE', parse_url(
                    $this->getHostAndPort(), PHP_URL_PORT
                )));

                fclose($socket);

                return;
            }

            // skip inotify for MacOS M2 Chipset
            if (strtolower(PHP_OS) !== 'darwin' && class_exists(Inotify::class)) {
                $process = Process::start(sprintf('php -S %s -t %s %s',
                    $this->getHostAndPort(), dirname($endpoint), $endpoint
                ));

                $inotify = new Inotify($this->getTokenLocation());

                # when token file is modified
                # kill php server and terminate inotify
                $inotify->event->on(IN_MODIFY, function (InotifyWatcher $inotify) use ($process, $output) {
                    # kill php server
                    # i not sure, but it needs to be + 1 to kill the php server
                    system('kill ' . $process->id() + 1);

                    # kill process and terminate inotify
                    if ($process->signal(SIGTERM) && $inotify->terminate()) {
                        $output->info('TOKEN HAS BEEN UPDATED');
                    }
                });

                $inotify->watch();
            } else {
                $process = Process::run(sprintf('php -S %s -t %s %s',
                    $this->getHostAndPort(), dirname($endpoint), $endpoint
                ));

                if (! $process->successful()) {
                    $output->error('COULD NOT START TOKEN LISTENER');
                }
            }
        }
    }

    /**
     * @return string
     */
    private function getTokenListenerEndPoint() : string
    {
        $endpoint = $this->gphoto->path('public/index.php');

        if (! file_exists($endpoint)) {
            if (! is_dir(dirname($endpoint))) {
                mkdir(dirname($endpoint), 0755, true);
            }

            file_put_contents($endpoint, file_get_contents(base_path(
                'stubs/listener.php'
            )));
        }

        return $endpoint;
    }

    /**
     * @return string
     */
    private function getTokenLocation() : string
    {
        $token = $this->gphoto->path('storage/token');

        if (! file_exists($token)) {
            if (! is_dir(dirname($token))) {
                mkdir(dirname($token), 0755, true);
            }

            file_put_contents($token, '');
        }

        return $token;
    }

    /**
     * @return string
     */
    private function getHostAndPort() : string
    {
        return parse_url($this->gphoto->config('host'), PHP_URL_HOST) . ':' . parse_url($this->gphoto->config('host'), PHP_URL_PORT);
    }
}
