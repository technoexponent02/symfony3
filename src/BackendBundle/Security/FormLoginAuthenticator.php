<?php
namespace BackendBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class FormLoginAuthenticator extends AbstractFormLoginAuthenticator
{
    private $router;
    private $encoder;
    private $em;
    private $session;
    private $container;

    public function __construct(RouterInterface $router, UserPasswordEncoderInterface $encoder, EntityManager $em, Session $session, Container $container)
    {
        $this->router = $router;
        $this->encoder = $encoder;
        $this->em = $em;
        $this->session = $session;
        $this->container = $container;
    }

    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/admin/login_check') {
          return;
        }
        $username = $request->request->get('_username');
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        $password = $request->request->get('_password');
      
        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];
        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $plainPassword = $credentials['password'];
        if ($this->encoder->isPasswordValid($user, $plainPassword)) {
            if($user->getIsActive() == 1){
                return true;
            }
        }

        throw new BadCredentialsException();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $user = $token->getUser();
        $user->setLoginCompany(NULL);
        $user->setSwitchUser(NULL);
        $this->em->flush();
        if($user->getUserType() == 1)
        {
            $url = $this->router->generate('admin_dashboard');
        }
        else
        {
            $user_company = $user->getUsersCompanies();
            if(count($user_company) > 0)
            {
                if(count($user_company) > 1)
                {
                    $url = $this->router->generate('user_choice_company');
                }
                else
                {
                    $user->setLoginCompany($user_company[0]->getId());
                    $this->em->flush();
                    
                    $this->container->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($user_company[0]->getCompany()->getCompanyDbHost(), $user_company[0]->getCompany()->getCompanyDbName(), $user_company[0]->getCompany()->getCompanyDbUser(), $user_company[0]->getCompany()->getCompanyDbPassword());

                    $url = $this->router->generate('user_dashboard');
                }
            }
            else
            {
                $this->session->set('login_error', 'You are not assign to any company. Please contact administrator');
                $url = $this->router->generate('logout');
            }
        }

        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
       $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
       $url = $this->router->generate('admin_login');

       return new RedirectResponse($url);
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('admin_login');
    }

    protected function getDefaultSuccessRedirectUrl()
    {
        return $this->router->generate('admin_dashboard');
    }

    public function supportsRememberMe()
    {
        return false;
    }
}