<?php

namespace AppBundle\Security\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;

class BilemoAuthenticator extends AbstractFormLoginAuthenticator
{
    private $client;
    private $bilemoTokenUrl;
    private $clientId;
    private $clientSecret;
    private $router;
    private $session;

    public function __construct(Client $client, $bilemoTokenUrl, $clientId, $clientSecret, $router, $session)
    {
        $this->client = $client;
        $this->bilemoTokenUrl = $bilemoTokenUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->router = $router;
        $this->session = $session;
    }

    /**
     * This authenticator will be skipped if return null
     */
    public function getCredentials(Request $request)
    {
        if ($request->request->has('_username')) {
            $username = $request->request->get('_username');
            $password = $request->request->get('_password');
            $request->getSession()->set(Security::LAST_USERNAME, $username);
            return array(
                'username' => $username,
                'password' => $password,
            );
        }

        return;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $redirect_uri = 'http://127.0.0.1:8001/create-user';
        $url = $this->bilemoTokenUrl.
            '?client_id='.$this->clientId.
            '&client_secret='.$this->clientSecret.
            '&redirect_uri='.$redirect_uri.
            '&grant_type=password&username='.$credentials['username'].'&password='.$credentials['password'];

        try {
            $response = $this->client->get($url);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $r = $e->getResponse();
            $error = json_decode($r->getBody()->getContents(), true);
            if ($error['error'] === 'invalid_grant') {
                throw new BadCredentialsException();
            }
        }
        
        $data = json_decode($response->getBody()->getContents(), true);
        if ($data['access_token'] !== null) {
            $this->session->set('access_token', $data['access_token']);
            return $userProvider->loadUserByUsername($data['access_token']);
        } else {
            return null;
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('login');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $productsPage = $this->router->generate('products');
        return new RedirectResponse($productsPage);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
       return parent::onAuthenticationFailure($request, $exception);
    }

    /**
     * Called when authentication is needed, but not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
