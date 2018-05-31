<?php
namespace BackendBundle\Handler;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class SessionIdleHandler
{

    protected $session;
    protected $token_storage;
    protected $router;
    protected $maxIdleTime;
    protected $container;

    public function __construct(SessionInterface $session, TokenStorage $token_storage, RouterInterface $router, Container $container, $maxIdleTime = 0)
    {
        $this->session = $session;
        $this->token_storage = $token_storage;
        $this->router = $router;
        $this->container = $container;
        $this->maxIdleTime = $maxIdleTime;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {

            return;
        }

        if ($this->maxIdleTime > 0) {

            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();
            $securityContext = $this->container->get('security.authorization_checker');
            if ($lapse > $this->maxIdleTime && !$securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
            {
                $this->token_storage->setToken(null);
                $this->session->getFlashBag()->set('info', 'You have been logged out due to inactivity.');

                // Change the route if you are not using FOSUserBundle.
                $event->setResponse(new RedirectResponse($this->router->generate('admin_login')));
            }
        }
    }

}