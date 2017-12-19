<?php

namespace AppBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebserviceUser implements UserInterface, EquatableInterface
{
	/**
	 * @Assert\NotBlank()
	 * @Assert\Regex(
     *     pattern="/\d/",
     *     match=false,
     *     message="Votre nom ne peut être composé d'un chiffre."
     * )
	 */
    private $lastname;

    /**
	 * @Assert\NotBlank()
	 * @Assert\Regex(
     *     pattern="/\d/",
     *     match=false,
     *     message="Votre prénom ne peut être composé d'un chiffre."
     * )
	 */
    private $firstname;

    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Cette email '{{ value }}' n'est pas valide.",
     *     checkMX = true
     * )
     */
    private $email;
    private $username;

    /**
	 * @Assert\NotBlank()
	 * @Assert\Regex(
     *     pattern="/ /",
     *     match=false,
     *     message="Votre mot de passe ne peut contenir des espaces."
     * )
     */
    private $password;
    private $accessToken;
    private $refreshToken;
    private $salt;

    public function __construct($values = [])
	{
		if (!empty($values))
		{
			$this->hydrate($values);
		}
	}

	public function hydrate($data)
	{
		foreach ($data as $arg => $value) 
		{
			$method = 'set'.ucfirst($arg);

			if (is_callable([$this, $method]))
			{
				$this->$method($value);
			}
		}
	}

	public function setLastname($lastname)
	{
		$this->lastname = $lastname;
	}

	public function setFirstname($firstname)
	{
		$this->firstname = $firstname;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function setUsername($username)
	{
		$this->username = $username;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}

	public function setRefreshToken($refreshToken)
	{
		$this->refreshToken = $refreshToken;
	}

	public function getAccessToken()
	{
		return $this->accessToken;
	}

	public function getRefreshToken()
	{
		return $this->refreshToken;
	}

	public function getLastname()
	{
		return $this->lastname;
	}

	public function getFirstname()
	{
		return $this->firstname;
	}

	public function getEmail()
	{
		return $this->email;
	}

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof WebserviceUser) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
