<?php

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

class OCSiracEmbedOauth
{
    const ALREADY_LOGGED = 1;

    const CONTINUE = 2;

    private static $instance;

    private $settings;

    private $provider;

    private function __construct()
    {
        $this->settings = eZINI::instance('ocsiracauth.ini')->group('EmbedOauth');
        if (!isset($this->settings['scopes'])) {
            $this->settings['scopes'] = 'profile';
        }
        $this->provider = new GenericProvider($this->settings);
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new OCSiracEmbedOauth();
        }

        return self::$instance;
    }

    public function supports($handler)
    {
        return $handler instanceof OCSiracReloadableHandlerInterface;
    }

    public function logout()
    {
        $logoutUrl = $this->settings['urlLogout'];
        if (isset($_SESSION['token'])) {
            try {
                $accessToken = new AccessToken(json_decode($_SESSION['token'], true));
                $refreshToken = $accessToken->getRefreshToken();
                $provider = $this->getProvider();
                $payload = http_build_query([
                    'client_id' => $this->settings['clientId'],
                    'client_secret' => $this->settings['clientSecret'],
                    'refresh_token' => $refreshToken,
                ]);
                $request = $provider->getAuthenticatedRequest(AbstractProvider::METHOD_POST, $logoutUrl, $accessToken, [
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => $payload,
                ]);
                $provider->getHttpClient()->send($request);
            } catch (Exception $e) {
                eZDebug::writeError($e->getMessage(), __METHOD__);
            }
        }
    }

    private function getProvider()
    {
        return $this->provider;
    }

    public function run(eZModule $module, OCSiracReloadableHandlerInterface $handler)
    {
        if (eZUser::currentUser()->isRegistered()) {
            $module->redirectTo('/');
            return self::ALREADY_LOGGED;
        }
        $provider = $this->getProvider();

        if (!isset($_GET['code'])) {
            $authorizationUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authorizationUrl);
            eZExecution::cleanExit();

        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            throw new Exception('Invalid state');

        } else {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);
            $_SESSION['token'] = json_encode($accessToken);
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $user = $resourceOwner->toArray();
            $handler->reload($this->flatten($user));
            return self::CONTINUE;
        }

        throw new Exception('Unhandled error');
    }

    private function flatten($array, $prefix = 'OAUTH_')
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . strtoupper($key) . '_');
            } else {
                $result[$prefix . strtoupper($key)] = $value;
            }
        }
        return $result;
    }
}


