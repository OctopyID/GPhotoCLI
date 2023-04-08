<?php

namespace App\Commands;

use App\Config\Config;
use App\Exceptions\InvalidTokenException;
use App\GPhoto;
use App\Listeners\TokenListener;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Photos\Types\Album;
use Illuminate\Support\Facades\Process;
use JetBrains\PhpStorm\NoReturn;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputOption;

class ListAlbumsCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $name = 'list:albums';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Show all albums';

    /**
     * @throws InvalidTokenException
     * @throws ApiException
     * @throws ValidationException
     */
    public function handle() : void
    {
        $auth = $this->option('auth');
        if (! $auth) {
            $auth = 'default';
        }

        $gphoto = new GPhoto($auth);

        $response = $gphoto->client()->listAlbums([
            'excludeNonAppCreatedData' => true,
        ]);

        $results = [];
        foreach ($response->iterateAllElements() as $album) {
            /**
             * @var Album $album
             */
            $results[] = [$album->getTitle(), $album->getMediaItemsCount(), $album->getProductUrl(),];
        }

        // sort by title
        usort($results, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        $numb = 1;
        $data = [];
        foreach ($results as $item) {
            $data[] = [$numb++, ...$item];
        }

        $this->table(['#', 'TITLE', 'MEDIA', 'URL'], $data);
    }

    /**
     * @return array[]
     */
    public function getOptions() : array
    {
        return [
            ['auth', null, InputOption::VALUE_REQUIRED, 'Authentication name'],
        ];
    }
}
