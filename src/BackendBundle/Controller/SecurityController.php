<?php

namespace BackendBundle\Controller;

use BackendBundle\Controller\MainController;
use BackendBundle\Form\Security\ForgotPassword;
use BackendBundle\Form\Security\ResetPassword;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class SecurityController extends MainController
{
    /**
     * @Route("/admin/login", name="admin_login")
     */
    public function loginAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            $user = $this->getUser();
            if($user->getUserType() == 1)
            {
                if( $user->getSwitchUser() != NULL)
                {
                    if($user->getLoginCompany() != NULL)
                    {
                        return $this->redirectToRoute('user_dashboard');
                    }
                    else
                    {
                        return $this->redirectToRoute('user_choice_company');
                    }
                }
                else
                {
                    if($user->getLoginCompany() != NULL)
                    {
                        return $this->redirectToRoute('user_dashboard');
                    }
                    else
                    {
                         return $this->redirectToRoute('admin_dashboard');
                    }
                }
            }
            else
            {
                if($user->getLoginCompany() != NULL)
                {
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    return $this->redirectToRoute('user_choice_company');
                }
            }
        }
        $helper = $this->get('security.authentication_utils');
        
        $login_error = '';
        if($request->getSession()->has('login_error'))
        {
            $login_error = $request->getSession()->get('login_error');
            $request->getSession()->remove('login_error');
        }
        return $this->render(
           'backend/default/login.html.twig',
           array(
               'last_username' => $helper->getLastUsername(),
               'error'         => $helper->getLastAuthenticationError(),
               'login_error' => $login_error,
           )
       );
    }

    /**
     * @Route("/admin/login_check", name="admin_login_check")
     */
    public function loginCheckAction()
    {
      /*echo "aaa";exit;*/
    }

    /**
     * @Route("/admin/forgot-password", name="admin_forgot_password")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function forgotPasswordAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            $user = $this->getUser();
            if($user->getUserType() == 1)
            {
                return $this->redirectToRoute('admin_dashboard');
            }
            else
            {
                if($user->getLoginCompany() != NULL)
                {
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    return $this->redirectToRoute('user_choice_company');
                }
            }
        }

        $form = $this->createForm(ForgotPassword::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];

            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('BackendBundle:User')->findOneBy(['email' => $email]);

            if (empty($user)) {
                $this->addFlash(
                    'noEmailFound',
                    'Sorry! No account found associated with the email.'
                );
            }
            else {
                // dump($user);

                $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 20);
                $expire_on = strtotime("+1 hour");
                $token = base64_encode($user->id.'####' . $randomString . '####' . $expire_on);

                $user->setForgetPasswordToken($token);
                $em->flush();
                $data  = [
                    'token' => $token,
                    'user' => $user
                ];

                $message = \Swift_Message::newInstance()
                    ->setSubject('Your Password Reset Link')
                    ->setFrom('mail-noreply@piggi.se')
                    ->setTo($user->email)
                    ->setBody(
                        $this->renderView(
                            'backend/Emails/forget_password.html.twig',
                            $data
                        ),
                        'text/html'
                    )
                ;
                $this->get('mailer')->send($message);

                $this->addFlash(
                    'success_forgot_pass',
                    'Reset password link send to your email successfully.'
                );
            }
        }

        return $this->render('backend/default/forgot_password.html.twig', [
            'forgotPassForm' => $form->createView()
        ]);

    }

    /**
     * @Route("/admin/reset-password/{token}", name="admin_reset_password")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function resetPasswordAction($token, Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            $user = $this->getUser();
            if($user->getUserType() == 1)
            {
                return $this->redirectToRoute('admin_dashboard');
            }
            else
            {
                if($user->getLoginCompany() != NULL)
                {
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    return $this->redirectToRoute('user_choice_company');
                }
            }
        }

        $form = $this->createForm(ResetPassword::class);

        $form->handleRequest($request);

        $decoded_token = base64_decode($token);
        $exploded_token = explode('####', $decoded_token);
        $user_id = $exploded_token[0];

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($user_id);

        // Check if token expired.
        if ($user->getForgetPasswordToken() == NULL ||  $user->getForgetPasswordToken() != $token) {
            return $this->redirectToRoute('admin_login');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $formData['password']);
            $user->setPassword($password);
            $user->setForgetPasswordToken(NULL);
            $em->flush();

            $this->addFlash(
                'success_reset_pass',
                'Your password has been reset successfully.'
            );
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('backend/default/reset_password.html.twig', [
            'resetPassForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        
    }

    /**
     * @Route("/user/choose-company", name="user_choice_company")
     */
    public function chooseCompanyAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            $user = $this->getUser();
            if($user->getUserType() == 1 && $user->getSwitchUser() == NULL)
            {
                if($user->getLoginCompany() != NULL)
                {
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    if(count($user->getUsersCompanies()) > 0)
                    {
                        $companies = new \Doctrine\Common\Collections\ArrayCollection();
                        foreach($user->getUsersCompanies() as $user_comp)
                        {
                            $companies[] = $user_comp->getCompany();
                        }
                        $form = $this->createFormBuilder([])
                                ->add('company_id', ChoiceType::class,
                                    [
                                        'choices' => $companies,
                                        'choice_label' => 'companyName',
                                        'choice_value' => 'id',
                                        'placeholder' => 'Choose Company',
                                        'expanded' => true,
                                        'constraints' => new Assert\NotBlank(array('message' => 'Please select a company.'))
                                    ]
                                    )
                                ->getForm();
                        $form->handleRequest($request);
                        if ($form->isSubmitted() && $form->isValid()) 
                        {
                            $data = $form->getData();
                            $company = $data['company_id'];
                            $repository = $this->getDoctrine()->getRepository('BackendBundle:UserCompany');
                            $UserCompany = $repository->findOneBy(['user' => $user, 'company' => $company]);
                            $em = $this->getDoctrine()->getManager();
                            $user->setLoginCompany($UserCompany->getId());
                            $em->flush();
                            $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                            return $this->redirectToRoute('user_dashboard');
                        }
                        return $this->render(
                           'backend/default/choose_company.html.twig',
                           ['form' => $form->createView()]
                       );
                    }
                    else
                    {
                        return $this->redirectToRoute('admin_dashboard');
                    }
                }
            }
            else
            {
                if($user->getLoginCompany() != NULL)
                {
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    $companies = new \Doctrine\Common\Collections\ArrayCollection();
                    if($user->getUserType() == 1)
                    {
                        $usersCompanies = $user->getSwitchUser()->getUsersCompanies();
                        foreach($usersCompanies as $user_comp)
                        {
                            $companies[] = $user_comp->getCompany();
                        }
                        $form = $this->createFormBuilder([])
                                ->add('company_id', ChoiceType::class,
                                    [
                                        'choices' => $companies,
                                        'choice_label' => 'companyName',
                                        'choice_value' => 'id',
                                        'placeholder' => 'Choose Company',
                                        'expanded' => true,
                                        'constraints' => new Assert\NotBlank(array('message' => 'Please select a company.'))
                                    ]
                                    )
                                ->getForm();
                        $form->handleRequest($request);
                        if ($form->isSubmitted() && $form->isValid()) 
                        {
                            $data = $form->getData();
                            $company = $data['company_id'];
                            $switchUser = $user->getSwitchUser();
                            $repository = $this->getDoctrine()->getRepository('BackendBundle:UserCompany');
                            $UserCompany = $repository->findOneBy(['user' => $switchUser, 'company' => $company]);
                            $em = $this->getDoctrine()->getManager();
                            $user->setLoginCompany($UserCompany->getId());
                            $em->flush();
                            $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                            return $this->redirectToRoute('user_dashboard');
                        }
                        return $this->render(
                           'backend/default/choose_company.html.twig',
                           ['form' => $form->createView()]
                       );
                    }
                    else
                    {
                        foreach($user->getUsersCompanies() as $user_comp)
                        {
                            $companies[] = $user_comp->getCompany();
                        }
                        $form = $this->createFormBuilder([])
                                ->add('company_id', ChoiceType::class,
                                    [
                                        'choices' => $companies,
                                        'choice_label' => 'companyName',
                                        'choice_value' => 'id',
                                        'placeholder' => 'Choose Company',
                                        'expanded' => true,
                                        'constraints' => new Assert\NotBlank(array('message' => 'Please select a company.'))
                                    ]
                                    )
                                ->getForm();
                        $form->handleRequest($request);
                        if ($form->isSubmitted() && $form->isValid()) 
                        {
                            $data = $form->getData();
                            $company = $data['company_id'];
                            $repository = $this->getDoctrine()->getRepository('BackendBundle:UserCompany');
                            $UserCompany = $repository->findOneBy(['user' => $user, 'company' => $company]);
                            $em = $this->getDoctrine()->getManager();
                            $user->setLoginCompany($UserCompany->getId());
                            $em->flush();
                            $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                            return $this->redirectToRoute('user_dashboard');
                        }
                        return $this->render(
                           'backend/default/choose_company.html.twig',
                           ['form' => $form->createView()]
                       );
                    }
                }
            }
        }
        else
        {
            return $this->redirectToRoute('logout');
        }
    }

    /**
     * @Route("/user/switch-access", name="user_switch_access")
     */
    public function switchAccessAction()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if(!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            return $this->redirectToRoute('admin_login');
        }
        else
        {
            $user = $this->getUser();
            $em = $this->getDoctrine()->getManager();
            $user->setLoginCompany(NULL);
            $em->flush();
            if($user->getUserType() == 1 && count($user->getUsersCompanies()) > 0)
            {
                if(count($user->getUsersCompanies()) == 1)
                {
                    $UserCompany = $user->getUsersCompanies();
                    $em = $this->getDoctrine()->getManager();
                    $user->setLoginCompany($UserCompany[0]->getId());
                    $em->flush();
                    $company = $UserCompany[0]->getCompany();
                    $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                    return $this->redirectToRoute('user_dashboard');
                }
                else
                {
                    return $this->redirectToRoute('user_choice_company');
                }
            }
            else
            {
                return $this->redirectToRoute('user_choice_company');
            }
        }
    }
}