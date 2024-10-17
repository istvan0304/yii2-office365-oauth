<?php

namespace istvan0304\yii2office365oauth\src;

use Yii;
use yii\authclient\OAuth2;
use yii\httpclient\Client;
use yii\web\HttpException;

/**
 * class Office365OAuth
 */
class Office365OAuth extends OAuth2
{
    public $authUrl;
    public $tokenUrl;
    public $apiBaseUrl;
    public $returnUrl;
    public $scope;
    public $name;
    public $title;
    public $login_hint;
    public $resource;
    public $selectParams;
    public $proxy;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Overrides default function to fix malformed url
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param array $params
     * @return string
     */
    public function buildAuthUrl(array $params = [])
    {
        $defaultParams = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->returnUrl,
            'login_hint' => $this->login_hint,
            'resource' => $this->resource,
        ];
        if (!empty($this->scope)) {
            $defaultParams['scope'] = $this->scope;
        }

        if ($this->validateAuthState) {
            $authState = $this->generateAuthState();
            $this->setState('authState', $authState);
            $defaultParams['state'] = $authState;
        }

        return $this->composeUrl($this->authUrl, array_merge($defaultParams, $params));
    }

    /**
     * @return string
     */
    protected function defaultName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    protected function defaultTitle()
    {
        return $this->title;
    }

    /**
     * For popup mode
     */
    protected function defaultViewOptions()
    {
        return [
            'popupWidth' => 800,
            'popupHeight' => 500,
        ];
    }

    /**
     * @param $request
     * @param $accessToken
     * @return void
     */
    public function applyAccessTokenToRequest($request, $accessToken)
    {
        /*$data = $request->getData();
        $data['access_token'] = $accessToken->getToken();
        $request->setData($data);*/
    }

    public function fetchAccessToken($authCode, array $params = [])
    {
        if ($this->validateAuthState) {
            $authState = $this->getState('authState');
            $incomingRequest = Yii::$app->getRequest();
            $incomingState = $incomingRequest->get('state', $incomingRequest->post('state'));
            if (
                !isset($incomingState)
                || empty($authState)
                || !Yii::$app->getSecurity()->compareString($incomingState, $authState)
            ) {
                throw new HttpException(400, 'Invalid auth state parameter.');
            }
            $this->removeState('authState');
        }

        $defaultParams = [
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getReturnUrl(),
        ];

        if ($this->enablePkce) {
            $authCodeVerifier = $this->getState('authCodeVerifier');
            if (empty($authCodeVerifier)) {
                // Prevent PKCE Downgrade Attack
                // https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics#name-pkce-downgrade-attack
                throw new HttpException(409, 'Invalid auth code verifier.');
            }
            $defaultParams['code_verifier'] = $authCodeVerifier;
            $this->removeState('authCodeVerifier');
        }

        $params = array_merge($defaultParams, $params);
        $params['client_id'] = $this->clientId;
        $params['client_secret'] = $this->clientSecret;

        $client = new Client();
        $request = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->tokenUrl)
            ->setData($params);

        if ($this->proxy != null) {
            $request->setOptions([
                'proxy' => $this->proxy
            ]);
        }

        $response = $request->send();
        $response = ($response != null ? json_decode($response->getContent(), true) : []);

        $token = $this->createToken(['params' => $response]);
        $this->setAccessToken($token);

        return $token;
    }

    /**
     * Gets new auth token to replace expired one.
     * @param OAuthToken $token expired auth token.
     * @return OAuthToken new auth token.
     */
    protected function initUserAttributes()
    {
        $apiSubUrl = 'me';

        if ($this->selectParams != null) {
            $apiSubUrl .= '?$select=' . $this->selectParams;
        }

        $client = new Client();
        $request = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiBaseUrl . '/' . $apiSubUrl)
            ->setData([])
            ->setHeaders(
                ['Authorization' => 'Bearer ' . $this->getAccessToken()->getToken()]
            );

        if ($this->proxy != null) {
            $request->setOptions([
                'proxy' => $this->proxy
            ]);
        }

        $response = $request->send();

        return ($response != null ? json_decode($response->getContent(), true) : []);
    }
}