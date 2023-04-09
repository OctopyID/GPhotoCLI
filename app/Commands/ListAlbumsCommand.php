<?php

namespace App\Commands;

use App\Exceptions\InvalidTokenException;
use App\GPhoto;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Photos\Types\Album;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputOption;

class ListAlbumsCommand extends Command
{
    /**
     * @var int
     */
    protected int $retry = 3;

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
        if (! $this->option('auth')) {
            $this->input->setOption('auth', 'default');
        }

        $gphoto = new GPhoto($this->option('auth'));

        try {
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
        } catch (Exception) {
            echo PHP_EOL;

            $this->components->warn(
                'EXPIRED TOKEN, RETRYING'
            );

            $gphoto->revoke();

            $this->call('auth:reload', [
                'name' => $this->option('auth'),
            ]);

            if ($this->retry > 0) {
                $this->retry--;
                $this->handle();
            }
        }
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
