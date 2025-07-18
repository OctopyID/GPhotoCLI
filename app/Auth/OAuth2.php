<?php

namespace App\Auth;

use App\Exceptions\InvalidAuthenticationException;
use App\GPhoto;
use Google\Auth\OAuth2 as GoogleOAuth2;
use Illuminate\Support\Arr;

class OAuth2 extends GoogleOAuth2
{
    /**
     * @var OAuth2
     */
    protected OAuth2 $oauth;

    /**
     *
     * @param  GPhoto $gphoto
     */
    public function __construct(protected GPhoto $gphoto)
    {
        parent::__construct([
            'scope'              => $this->gphoto->scopes(),
            'redirectUri'        => $this->gphoto->config('redirect'),
            'clientId'           => $this->gphoto->config('auth.client_id'),
            'clientSecret'       => $this->gphoto->config('auth.client_secret'),
            'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
            'authorizationUri'   => 'https://accounts.google.com/o/oauth2/v2/auth',
        ]);
    }

    /**
     * @return string
     * @throws InvalidAuthenticationException
     */
    public function fetchNewAccessToken() : string
    {
        if (! file_exists($this->gphoto->path('storage/token'))) {
            throw new InvalidAuthenticationException(
                sprintf("'%s' auth name is not exists. Use --auth option to specify the authentication name", $this->gphoto->name()),
            );
        }

        $this->setCode(file_get_contents($this->gphoto->path(
            'storage/token',
        )));

        return Arr::get($this->fetchAuthToken(), 'access_token');
    }
}
