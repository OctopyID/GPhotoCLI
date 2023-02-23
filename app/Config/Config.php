<?php

namespace App\Config;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Config
{
    /**
     * @var Filesystem
     */
    protected Filesystem $storage;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->storage = Storage::disk('local');
    }

    /**
     * @param  string $name
     * @param  string $key
     * @return mixed
     */
    public function get(string $name, string $key) : mixed
    {
        return Arr::get($this->all($name), $key);
    }

    /**
     * @param  string $name
     * @return array
     */
    public function all(string $name) : array
    {
        $config = md5($name) . '/config.json';

        if (! $this->storage->exists($config)) {
            return [];
        }

        return json_decode($this->storage->get($config), true);
    }

    /**
     * @throws Exception
     */
    public function store(array $config) : void
    {
        $hash = md5($config['name']);

        $auth = json_decode(file_get_contents($config['auth']), true);

        if (! isset($auth['web'])) {
            throw new Exception('INVALID AUTH FILE');
        }

        $this->storage->put($hash . '/config.json', json_encode(array_merge($config, [
            'auth' => $auth['web'],
        ]), JSON_PRETTY_PRINT));
    }
}
