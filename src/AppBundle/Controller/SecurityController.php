<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Security\User\WebserviceUser;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use GuzzleHttp\Client;
use Symfony\Component\Form\FormError;


class SecurityController extends Controller
{
    /**
     * @Route("/login/", name="login")
     */
    public function loginAction(Request $request, AuthenticationUtils $authUtils)
    {
        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));

    }

    /**
     * @Route("/register", name="register")
     */
    public function registerAction(Request $request)
    {
        $newUser = new WebserviceUser();
        $form = $this->createFormBuilder($newUser)
            ->add('lastname', TextType::class)
            ->add('firstname', TextType::class)
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'email'     => $newUser->getEmail(),
                'username'  => $newUser->getUsername(),
                'password'  => $newUser->getPassword(),
                'lastname'  => $newUser->getLastname(),
                'firstname' => $newUser->getFirstname(),
                'client_id' => $this->getParameter('client_id'),
                'client_secret' => $this->getParameter('client_secret'),
                'grant_type'    => 'password',
                'redirect_uri'  => 'http://127.0.0.1:8001/products'
            ];
            
            $client = new Client();
            try {
                $r = $client->request('POST', 'http://127.0.0.1:8000/register', [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $error = json_decode($e->getResponse()->getBody()->getContents(), true);
                $form->addError(new FormError($error['message']));
                return $this->render('security/register.html.twig', array(
                    'form'      => $form->createView(),
                ));
            }
           
            $response = json_decode($r->getBody()->getContents(), true);
            if ($response['access_token'] !== null) {
                $this->get('session')->set('access_token', $response['access_token']);
                return $this->get('security.authentication.guard_handler')
                    ->authenticateUserAndHandleSuccess(
                        $this->get('AppBundle\Security\User\WebserviceUserProvider')->loadUserByUsername('test'),
                        $request,
                        $this->get('AppBundle\Security\User\BilemoAuthenticator'),
                        'main'
                    );
            }
        }

        return $this->render('security/register.html.twig', array(
            'form'      => $form->createView(),
        ));
    }
}
