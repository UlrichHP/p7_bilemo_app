<?php

namespace AppBundle\Security\User;

use AppBundle\Security\User\WebserviceUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use GuzzleHttp\Client;

class WebserviceUserProvider implements UserProviderInterface
{
    private $client;

    public function __construct(Client $client, $session)
    {
        $this->client = $client;
        $this->session = $session;
    }

    public function loadUserByUsername($username)
    {
        $api = 'http://127.0.0.1:8000/api/users/me';
        $accessToken = $this->session->get('access_token');
        // make a call to your webservice here
        $response = $this->client->get($api, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
            ]
        ]);
        $userData = json_decode($response->getBody()->getContents(), true);
        // pretend it returns an array on success, false if there is no user

        if ($userData) {
            $user = new WebserviceUser($userData);
 
            return $user;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof WebserviceUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return WebserviceUser::class === $class;
    }
}
