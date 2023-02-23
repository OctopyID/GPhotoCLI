<?php

namespace App;

use App\Auth\Credentials\Credential;
use App\Auth\OAuth2;
use App\Config\Config;
use App\Exceptions\InvalidTokenException;
use Google\ApiCore\ValidationException;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Illuminate\Support\Facades\Storage;

final class GPhoto
{
    /**
     * @var Config
     */
    protected readonly Config $config;

    /**
     * @var PhotosLibraryClient
     */
    protected PhotosLibraryClient $client;

    /**
     * @param  string $name
     */
    public function __construct(protected readonly string $name)
    {
        $this->config = new Config;
    }

    /**
     * @return OAuth2
     */
    public function oauth() : OAuth2
    {
        return new OAuth2($this);
    }

    /**
     * @throws ValidationException
     * @throws InvalidTokenException
     */
    public function client() : PhotosLibraryClient
    {
        return $this->client = new PhotosLibraryClient([
            'credentials' => (new Credential($this))->getCredential(),
        ]);
    }

    /**
     * @return bool
     */
    public function revoke() : bool
    {
        return (new Credential($this))->deleteCredential();
    }

    /**
     * @param  string|null $key
     * @return mixed
     */
    public function config(string $key = null) : mixed
    {
        return $this->config->get($this->name, $key);
    }

    /**
     * @param  string $path
     * @return string
     */
    public function path(string $path) : string
    {
        return Storage::disk('local')->path(md5($this->name) . '/' . $path);
    }

    /**
     * @return string[]
     */
    public function scopes() : array
    {
        return [
            'https://www.googleapis.com/auth/photoslibrary',
            'https://www.googleapis.com/auth/photoslibrary.sharing',
            'https://www.googleapis.com/auth/photoslibrary.edit.appcreateddata',
        ];
    }
}
