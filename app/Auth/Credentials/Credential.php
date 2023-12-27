<?php

namespace App\Auth\Credentials;

use App\Exceptions\InvalidAuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\GPhoto;
use Google\Auth\Credentials\UserRefreshCredentials;
use GuzzleHttp\Exception\ClientException;

class Credential
{
    /**
     * @param  GPhoto $gphoto
     */
    public function __construct(protected GPhoto $gphoto)
    {
        //
    }

    /**
     * @return UserRefreshCredentials
     * @throws InvalidTokenException|InvalidAuthenticationException
     */
    public function getCredential() : UserRefreshCredentials
    {
        if (file_exists($this->gphoto->path('storage/state'))) {
            return $this->getFromCache();
        }

        return $this->getFromGoogle();
    }

    /**
     * @return bool
     */
    public function deleteCredential() : bool
    {
        if (file_exists($this->gphoto->path('storage/state'))) {
            return unlink($this->gphoto->path('storage/state'));
        }

        return true;
    }

    /**
     * @return UserRefreshCredentials
     * @throws InvalidTokenException|InvalidAuthenticationException
     */
    private function getFromGoogle() : UserRefreshCredentials
    {
        try {
            $credential = new UserRefreshCredentials($this->gphoto->scopes(), [
                'client_id'     => $this->gphoto->config('auth.client_id'),
                'client_secret' => $this->gphoto->config('auth.client_secret'),
                'refresh_token' => $this->gphoto->oauth()->fetchNewAccessToken(),
            ]);

            if (file_put_contents($this->gphoto->path('storage/state'), serialize($credential))) {
                return $credential;
            }
        } catch (ClientException $exception) {
            if ($this->deleteCredential()) {
                throw new InvalidTokenException;
            }

            throw $exception;
        }
    }

    /**
     * @return UserRefreshCredentials
     */
    private function getFromCache() : UserRefreshCredentials
    {
        return unserialize(file_get_contents(
            $this->gphoto->path('storage/state')
        ));
    }
}
