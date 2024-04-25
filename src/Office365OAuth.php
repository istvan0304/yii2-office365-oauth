<?php

namespace istvan0304\yii2office365oauth\src;

use yii\authclient\OAuth2;

class Office365OAuth extends OAuth2
{
    public $authUrl;

    public $tokenUrl;

    public $apiBaseUrl;

    public $returnUrl;

    public $scope;

    public $name;

    public $title;

    public $prompt;

    public $login_hint;

    public $resource;

    public function init()
    {
        parent::init();
        // initialize the module with the config uration loaded from config.php
        \Yii::configure($this, require(__DIR__ . '/config.php'));
        \Yii::configure($this, [
            'authUrl' => getenv('AUTH_URL'),
            'tokenUrl' => getenv('AUTH_TOKEN_URL'),
            'apiBaseUrl' => getenv('GRAPH_URL'),
            'returnUrl' => getenv('AUTH_RETURN_URL'),
            'scope' => getenv('AUTH_SCOPE'),
            'resource' => getenv('AUTH_RESOURCE_URL'),
        ]);
        var_dump($this);
    }

    /**
     * Overrides default function to fix malformed url
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function buildAuthUrl(array $params = [])
    {
        $defaultParams = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->returnUrl,
            'prompt' => $this->prompt,
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

    protected function defaultName()
    {
        return $this->name;
    }

    protected function defaultTitle()
    {
        return $this->title;
    }

    /**
     * For popup mode
     */
//    protected function defaultViewOptions()
//    {
//        return [
//            'popupWidth' => 800,
//            'popupHeight' => 500,
//        ];
//    }

    public function applyAccessTokenToRequest($request, $accessToken)
    {
        /*$data = $request->getData();
        $data['access_token'] = $accessToken->getToken();
        $request->setData($data);*/
    }

    /**
     * Gets new auth token to replace expired one.
     * @param OAuthToken $token expired auth token.
     * @return OAuthToken new auth token.
     */
    protected function initUserAttributes()
    {
        return $this->api('me?$select=id,displayName,userPrincipalName,mail,photo,mobilePhone,preferredLanguage,onPremisesExtensionAttributes', 'GET',
            NULL,
            ['Authorization' => 'Bearer '.$this->getAccessToken()->getToken()] //HEADER
        );
    }
}