<?php
namespace BackendBundle\Security;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class LogoutListener implements LogoutSuccessHandlerInterface
{

    private $token_storage;
    private $em;
    private $router;
    private $session;

    public function __construct(TokenStorage $token_storage, EntityManager $em, RouterInterface $router, Session $session)
    {
        $this->token_storage = $token_storage;
        $this->em = $em;
        $this->router = $router;
        $this->session = $session;
    }

    public function onLogoutSuccess(Request $request)
    {
        $user = $this->token_storage->getToken()->getUser();
        $user->setLoginCompany(NULL);
        $user->setSwitchUser(NULL);
        $this->em->flush();
        if($this->session->has('active_dynamic_conn'))
        {
            $this->session->remove('active_dynamic_conn');
        }
         //add code to handle $user here
         //...
        $response = new RedirectResponse($this->router->generate('admin_login'));
        return $response;
    }
}