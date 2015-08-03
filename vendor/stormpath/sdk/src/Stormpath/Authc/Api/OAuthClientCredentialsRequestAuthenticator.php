<?php

namespace Stormpath\Authc\Api;

use JWT;
use Stormpath\Client;
use Stormpath\Exceptions\RequestAuthenticatorException;

class OAuthClientCredentialsRequestAuthenticator extends InternalRequestAuthenticator implements RequestAuthenticator
{

    public function authenticate(Request $request)
    {
        if (!$this->application)
            throw new \InvalidArgumentException('The application must be set.');

        $this->validateGrantType($request);

        $apiKey = $this->getApiKeyById($request);

        if($this->isValidApiKey($request, $apiKey))
        {
            $account = $apiKey->account;
        }

        if($this->isValidAccount($account))
        {
            $token = $this->buildTokenResponse($apiKey);

            return new OAuthClientCredentialsAuthenticationResult($this->application, $apiKey, $token);
        }
    }

    private function buildTokenResponse($apiKey)
    {
        $token = array(
            'access_token' => $this->buildAccessToken($apiKey),
            'token_type' => 'bearer',
            'expires_in' => time() + 3600
        );

        return json_encode($token);
    }

    private function buildAccessToken($apiKey)
    {
        $apiSecret = Client::getInstance()->getDataStore()->getApiKey()->getSecret();
        $jwt = JWT::encode(
            array(
                "sub" => $apiKey->id,
                "iss" => $this->application->getHref(),
                "iat" => time(),
                "exp" => time() + 3600

            ),
            $apiSecret
        );

        return $jwt;
    }

    private function validateGrantType($request)
    {
        if (!$request->isBasicAuthorization())
            throw new RequestAuthenticatorException('The type of Authentication is incorrect!');

        if (!$request->hasGrantType())
            throw new RequestAuthenticatorException('The grant_type query parameter must be used');
    }

}