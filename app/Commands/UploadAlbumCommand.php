<?php

namespace App\Commands;

use App\GPhoto;
use FilesystemIterator;
use LaravelZero\Framework\Commands\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputOption;

class UploadAlbumCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $name = 'upload:album';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Upload album to google photos';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() : void
    {
        if (! $this->option('auth')) {
            $this->input->setOption('auth', 'default');
        }

        $gphoto = new GPhoto($this->option('auth'));

        if (! file_exists($gphoto->path('config.json'))) {
            $this->components->warn(
                'CONFIG NOT EXISTS run "gphoto auth:create" instead'
            );

            return;
        }
        if (! is_dir($this->argument('source'))) {
            $this->components->error('SOURCE IS NOT A DIRECTORY');

            return;
        }

        if (! $this->option('multiple')) {
            $this->call('upload:photo', [
                'source'  => $this->argument('source'),
                '--auth'  => $this->option('auth'),
                '--album' => basename($this->argument('source')),
                '--skip'  => $this->option('skip'),
            ]);

            return;
        }

        // upload multiple albums
        $albums = $this->findAlbums($this->argument(
            'source'
        ));

        foreach ($albums as $name => $path) {
            $this->call('upload:photo', [
                'source'  => $path,
                '--album' => $name,
                '--auth'  => $this->option('auth'),
                '--skip'  => $this->option('skip'),
            ]);
        }
    }

    /**
     * @param  string $source
     * @return array
     */
    private function findAlbums(string $source) : array
    {
        $albums = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source), FilesystemIterator::SKIP_DOTS
        );

        /**
         * @var $file SplFileInfo
         */
        foreach ($iterator as $file) {
            // skip dot files
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            if ($file->isDir()) {
                $albums[$file->getFilename()] = $file->getPathname();
            }
        }

        return $albums;
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
            ['multiple', null, InputOption::VALUE_NONE, 'Upload multiple albums'],
            ['skip', null, InputOption::VALUE_OPTIONAL, 'Skip certain extensions'],
        ];
    }
}
