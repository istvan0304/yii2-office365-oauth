Yii2 sign in with microsoft work or school account (Office 365)
=================

Requirements
------------
- php >=7.2

Installation
------------
The preferred way to install this extension is through composer.

- Run

    $ php composer.phar require istvan0304/yii2-office365-oauth "dev-master"
    
or add:
    
        "istvan0304/yii2-office365-oauth": "dev-master"
        
to the require section of your application's composer.json file.

Usage
------------

- In controller apply:

        public function actions()
        {
          return [
            'o365auth' => [
              'class' => 'cranedev\authclientO365\Office365AuthAction',
              'successCallback' => [$this, 'onAuthSuccess'],
            ],
          ];
        }

        public function onAuthSuccess($client)
        {
            // Handle login...
      }
        
- In view:

        <?= AuthChoice::widget([
          'baseAuthUrl' => ['site/o365auth'],
          'popupMode' => false,
      ]) ?>
                
  OR create a link

- Set Environment variables

- Setup http client

        return [
          'components' => [
              'authClientCollection' => [
                'class' => 'yii\authclient\Collection',
            'clients' => [
                'o365' => [
                    'class' => 'istvan0304\yii2office365oauth\src\Office365OAuth',
                    'clientId' => getenv('AUTH_CLIENT_ID'),
                    'clientSecret' => getenv('AUTH_CLIENT_SECRET'),
                    'authUrl' => getenv('AUTH_URL'),
                    'tokenUrl' => getenv('AUTH_TOKEN_URL'),
                    'apiBaseUrl' => getenv('GRAPH_URL'),
                    'returnUrl' => getenv('AUTH_RETURN_URL'),
                    'scope' => getenv('AUTH_SCOPE'),
                    'resource' => getenv('AUTH_RESOURCE_URL'),
                    'title' => '5464',
          //                    'prompt' => '',
          //                    'login_hint' => '',
              ],
          ],
              //...
            ],
          // ...
      ];