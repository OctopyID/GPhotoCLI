<?php

namespace App\Commands;

use App\Exceptions\InvalidAuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\GPhoto;
use FilesystemIterator;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use LaravelZero\Framework\Commands\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputOption;

class UploadPhotoCommand extends Command
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
    protected $name = 'upload:photo';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Upload photo to google photos';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws ValidationException
     * @throws InvalidAuthenticationException
     */
    public function handle() : void
    {
        if (! $this->option('auth')) {
            $this->input->setOption('auth', 'default');
        }

        $gphoto = new GPhoto($this->option('auth'));

        if (! file_exists($gphoto->path('storage/token'))) {
            $this->components->error(
                'TOKEN NOT EXISTS run "gphoto auth:create" instead'
            );

            return;
        }

        $photos = $this->getPhotosFromSource();

        if (empty($photos)) {
            return;
        }

        try {
            $client = $gphoto->client();

            $this->components->info('UPLOADING PHOTOS' . ($this->option('album') ? ' TO ALBUM : ' . $this->option('album') : ''));

            config([
                'cache.stores.file.path' => $gphoto->path('storage/cache'),
            ]);

            $media = [];
            $total = count($photos);
            foreach ($photos as $number => $photo) {
                $task = sprintf('[%s/%s] %s', str_pad($number + 1, strlen($total), '0', STR_PAD_LEFT), $total, basename(
                    $photo
                ));

                $this->components->task($task, function () use (&$media, $client, $photo, $gphoto) {
                    // get image token from cache
                    $media[] = Cache::sear(md5_file($photo), function () use ($photo, $client) {
                        return PhotosLibraryResourceFactory::newMediaItem($client->upload(file_get_contents($photo)));
                    });
                });
            }

            if (! Cache::has(md5(implode(',', $photos)))) {
                $options = [];
                if ($this->option('album')) {
                    $options = [
                        'albumId' => $this->getAlbumId($client),
                    ];
                }

                foreach (array_chunk($media, 50) as $chunk) {
                    $client->batchCreateMediaItems($chunk, $options);
                }

                Cache::sear(md5(implode(',', $photos)), function () {
                    return true;
                });
            }

            echo PHP_EOL;
        } catch (InvalidTokenException|ClientException $exception) {
            echo PHP_EOL;

            $this->components->warn(
                'AN ERROR OCCURRED WHILE UPLOADING PHOTOS, RETRYING'
            );

            $gphoto->revoke();

            $this->call('auth:reload', [
                'name' => $this->option('auth'),
            ]);

            if ($this->retry > 0) {
                $this->retry--;
                $this->handle();
            }
        } catch (ApiException $exception) {
            $this->components->error($exception->getMessage());
        }
    }

    /**
     * @param  PhotosLibraryClient $library
     * @return mixed
     * @throws ApiException
     * @throws ValidationException
     */
    private function getAlbumId(PhotosLibraryClient $library) : mixed
    {
        return Cache::rememberForever(md5($this->option('album') . ':album'), function () use ($library) {
            $albums = $library->listAlbums([
                'excludeNonAppCreatedData' => true,
            ]);

            // check if album exists
            foreach ($albums->iterateAllElements() as $album) {
                if ($album->getTitle() === $this->option('album')) {
                    return $album->getId();
                }
            }

            // if not create new album
            $album = PhotosLibraryResourceFactory::album(
                $this->option('album')
            );

            return $library->createAlbum($album)->getId();
        });
    }

    /**
     * @return array
     */
    private function getPhotosFromSource() : array
    {
        $source = $this->argument('source');
        if (! file_exists($source) && ! is_dir($source)) {
            $this->components->error('SOURCE MUST BE A FILE OR DIRECTORY');
        }

        if (is_file($source)) {
            return [$source];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), FilesystemIterator::SKIP_DOTS);

        $photos = [];
        /**
         * @var SplFileInfo $file
         */
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isAcceptableMimeType($file)) {
                $photos[] = $file->getPathname();
            }
        }

        if ($this->option('skip')) {
            $extensions = array_map('strtolower', array_map('trim', explode(',', $this->option('skip'))));

            $photos = array_filter($photos, function ($photo) use ($extensions) {
                return ! in_array(strtolower(pathinfo($photo, PATHINFO_EXTENSION)), $extensions);
            });
        }

        return array_values($photos);
    }

    /**
     * @return array[]
     */
    public function getArguments() : array
    {
        return [
            ['source', InputOption::VALUE_REQUIRED, 'Path to photo'],
        ];
    }

    /**
     * @return array[]
     */
    public function getOptions() : array
    {
        return [
            ['auth', null, InputOption::VALUE_REQUIRED, 'Authentication name'],
            ['album', null, InputOption::VALUE_REQUIRED, 'Album name'],
            ['skip', null, InputOption::VALUE_OPTIONAL, 'Skip certain extensions'],
        ];
    }

    /**
     * @param  SplFileInfo $file
     * @return bool
     */
    private function isAcceptableMimeType(SplFileInfo $file) : bool
    {
        $mime = mime_content_type($file->getPathname());

        $prefixes = ['image/', 'video/'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($mime, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
