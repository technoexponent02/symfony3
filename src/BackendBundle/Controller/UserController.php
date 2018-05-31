<?php

namespace BackendBundle\Controller;

use BackendBundle\Controller\MainController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackendBundle\Form\UserForm;
use BackendBundle\Form\UserEditForm;
use BackendBundle\Form\UserCompanyForm;
use BackendBundle\Entity\User;
use BackendBundle\Entity\UserCompany;
use CompanyBundle\Entity\UserPermission;
use BackendBundle\Form\AccountEditForm;
use BackendBundle\Form\ChangePasswordForm;
use Symfony\Component\Form\FormError;

class UserController extends MainController
{
    /**
     * @Route("/admin/user", name="admin_users")
     */
    public function indexAction(Request $request)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        $repository = $this->getDoctrine()
            ->getRepository('BackendBundle:User');
        
        $users = $repository->findBy(['isActive' => 1], ['userType' => 'ASC']);
        // replace this example code with whatever you need
        return $this->render('backend/user/list.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
            'users' => $users,
        ]);
    }
    /**
     * @Route("/admin/user/create", name="admin_user_create")
     */
    public function createAction(Request $request)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }

        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            /*try
            {*/
                $em->persist($user); 
                $em->flush();
            /*}
            catch(\Exception $e)
            {
                var_dump($e->getMessage());
            }
            exit;*/
             $this->addFlash('success','User has been created successfully.');
            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            $data  = [
                    'user' => $user,
                    'password' => $user->getPlainPassword(),
                ]; 
             
            $message = \Swift_Message::newInstance()
                ->setSubject('New account')
                ->setFrom('mail-noreply@piggi.se')
                ->setTo($user->email)
                ->setBody(
                    $this->renderView(
                        'backend/Emails/new_user.html.twig',
                        $data
                    ),
                    'text/html'
                )
            ;
            $this->get('mailer')->send($message);
            return $this->redirectToRoute('admin_user_create');
        }
        return $this->render(
            'backend/user/create.html.twig',
            array('form' => $form->createView())
        );
    }
    /**
     * @Route("/admin/user/edit/{id}", name="admin_user_edit")
     */
    public function editAction(Request $request, $id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_users');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);
        $form = $this->createForm(UserEditForm::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($request->getMethod() == 'POST') 
        {
            if ($form->isSubmitted() && $form->isValid()) 
            {
                if($user->getPlainPassword())
                {
                    $password = $this->get('security.password_encoder')
                                ->encodePassword($user, $user->getPlainPassword());
                    $user->setPassword($password);
                }
                // perform some action, such as save the object to the database
                $em->flush();

                if($user->getPlainPassword())
                {
                    $data  = [
                            'user' => $user,
                            'password' => $user->getPlainPassword(),
                        ]; 
                     
                    $message = \Swift_Message::newInstance()
                        ->setSubject('Change user password')
                        ->setFrom('mail-noreply@piggi.se')
                        ->setTo($user->email)
                        ->setBody(
                            $this->renderView(
                                'backend/Emails/change_user_password.html.twig',
                                $data
                            ),
                            'text/html'
                        )
                    ;
                    $this->get('mailer')->send($message);
                }
                $this->addFlash('success','User has been updated successfully.');
                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user
                return $this->redirectToRoute('admin_user_edit', ['id' => $id]);
            }
        }
       
        return $this->render(
            'backend/user/edit.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/admin/user/delete/{id}", name="admin_user_delete")
     */
    public function deleteAction($id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_users');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);
        if($user)
        {
            $msg = 'User has been archived successfully.';
            if($user->getIsActive() == 0)
            {
                $msg = 'User has been activated successfully.';
                $user->setIsActive(1);
            }
            else
            {
                $user_name = $user->getUsername();
                $user_name .= '-archived'.$user->getId();
                $user_email = $user->getEmail();
                $user_email .= '-archived'.$user->getId();
                $user->setUsername($user_name);
                $user->setEmail($user_email);
                $user->setIsActive(0);
            }
            
            $em->flush();
            $this->addFlash('delete_user_success', $msg);
        }
        return $this->redirectToRoute('admin_users');
    }

    /**
     * @Route("/admin/user/send-reset-password-link/{id}", name="admin_user_send_reset_password_link")
     */
    public function sendResetPasswordLinkAction($id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_users');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);
        if($user)
        {
            
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
            $this->addFlash('delete_user_success', 'Password reset link send successfully.');
        }
        return $this->redirectToRoute('admin_users');
    }

    /**
     * @Route("/admin/test-multidb", name="admin_user_check_multidb")
     */
    public function testMultidbAction()
    {
        /*$this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch('symfony_portalen_company2', 'root', NULL);*/
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('BackendBundle:User')->findAll();
        $html = '<p>All users:';
        if(count($users) > 0)
        {
            foreach($users as $usr)
            {
                $html .= $usr->username. ', ';
            }
        }

        $html .= '</p><br/>';

        $companyEm1 = $this->getDoctrine()->getManager('company');
        $company1 = $companyEm1->getRepository('CompanyBundle:CompanyDetails')->find(1);
        if(count($company1) > 0)
        {
            $html .=  '<p>Company 1 name :' .  $company1->companyName . '</p><br/>';  
        }

        /*$companyEm2 = $this->getDoctrine()->getManager('company2');
        $company2 = $companyEm2->getRepository('CompanyBundle:CompanyDetails')->find(1);
        if(count($company2) > 0)
        {
            $html .=  '<p>Company 2 name :' .  $company2->companyName . '</p><br/>';  
        }*/
        return new Response(
            '<html><body>'.$html.'</body></html>'
        );
    }

    /**
     * @Route("/admin/user/add-role-company", name="admin_user_add_role_company")
     */
    public function addRoleCompanyAction(Request $request)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }

        $userCompany = new UserCompany();
        $form = $this->createForm(UserCompanyForm::class, $userCompany, ['privilage_user' => $this->privilegeUser()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $form_data = $request->request->all();
            $data = $form->getData();
            $em = $this->getDoctrine()->getManager();
            /*$data = $form->getData();
            print_r($data->user);exit;*/
            if(!empty($form_data['modules_ids']))
            {
                foreach ($userCompany->getCompany()->getModules() as $module) 
                {
                    if(in_array($module->getId(), $form_data['modules_ids']))
                    {
                        $userCompany->addModule($module);
                    }
                }
                $em->persist($userCompany); 
                $em->flush();
                if(!empty($form_data['permissions']))
                {
                    $company = $data->company;
                    $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                    $companyEm = $this->getDoctrine()->getManager('company');
                    foreach($form_data['permissions'] as $permission_id) 
                    {
                        $permission = $companyEm->getRepository('CompanyBundle:Permission')->find($permission_id);
                        $user_permission = new UserPermission();
                        $user_permission->setUserId($data->user->getId());
                        $user_permission->setPermission($permission);
                        $companyEm->persist($user_permission); 
                        $companyEm->flush();
                    }
                }
                $this->addFlash('success','User has been assigned to companies successfully.');
                return $this->redirectToRoute('admin_user_add_role_company');
            }
            else
            {
                $this->addFlash('error','Please select modules.');
                return $this->redirectToRoute('admin_user_add_role_company');
            }
            
        }
        
        return $this->render(
            'backend/user/add_user_role_company.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/admin/user/roles-companies", name="admin_user_roles_companies")
     */
    public function roleCompaniesAction()
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }

        $repository = $this->getDoctrine()
            ->getRepository('BackendBundle:UserCompany');
        
        $usersCompanies = $repository->findBy(['isActive' => 1]);

        return $this->render('backend/user/user_role_complany_list.html.twig', [
            'usersCompanies' => $usersCompanies,
        ]);
    }

    /**
     * @Route("/admin/user/edit-role-company/{id}", name="admin_user_edit_role_company")
     */
    public function editRoleCompanyAction(Request $request, $id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_user_roles_companies');
        }

        $em = $this->getDoctrine()->getManager();
        $userCompany = $em->getRepository('BackendBundle:UserCompany')->find($id);
        $form = $this->createForm(UserCompanyForm::class, $userCompany, ['privilage_user' => $this->privilegeUser()]);

        $company_modules = $userCompany->getCompany()->getModules();
        $old_userCompany_modules = $userCompany->getModules();
        $selected_userCompany_module_ids = [0];

        if(count($old_userCompany_modules) > 0)
        {
            foreach($old_userCompany_modules as $old_userCompany_module)
            {
                $selected_userCompany_module_ids[] = $old_userCompany_module->getId();
            }
        }


        $oldCompany = $userCompany->getCompany();
        $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($oldCompany->getCompanyDbHost(), $oldCompany->getCompanyDbName(), $oldCompany->getCompanyDbUser(), $oldCompany->getCompanyDbPassword());
        $oldCompanyEm = $this->getDoctrine()->getManager('company');

        $permissions = $oldCompanyEm->getRepository('CompanyBundle:Permission')->findAll();

        $oldSelectedPermissions = $oldCompanyEm->getRepository('CompanyBundle:UserPermission')->findBy(['userId' => $userCompany->getUser()->getId()]);
       
        $selected_permissions_ids = [0];

        if(count($oldSelectedPermissions) > 0)
        {
            foreach($oldSelectedPermissions as $sl_permsn)
            {
                $selected_permissions_ids[] = $sl_permsn->getPermission()->getId();
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $form_data = $request->request->all();
            $data = $form->getData();
            if(!empty($form_data['modules_ids']))
            {
                if(count($oldSelectedPermissions) > 0)
                {
                    foreach($oldSelectedPermissions as $sl_permsn_obj)
                    {
                        $oldCompanyEm->remove($sl_permsn_obj);
                        $oldCompanyEm->flush();
                    }
                }

                if(count($old_userCompany_modules) > 0)
                {
                    foreach($old_userCompany_modules as $old_userCompany_module)
                    {
                        $userCompany->removeModule($old_userCompany_module);
                    }
                }
                
                foreach($userCompany->getCompany()->getModules() as $module) 
                {
                    if(in_array($module->getId(), $form_data['modules_ids']))
                    {
                        $userCompany->addModule($module);
                    }
                }

                $em->flush();

                if(!empty($form_data['permissions']))
                {
                    $company = $data->company;
                    $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                    $companyEm = $this->getDoctrine()->getManager('company');
                    foreach($form_data['permissions'] as $permission_id) 
                    {
                        $permission = $companyEm->getRepository('CompanyBundle:Permission')->find($permission_id);
                        $user_permission = new UserPermission();
                        $user_permission->setUserId($data->user->getId());
                        $user_permission->setPermission($permission);
                        $companyEm->persist($user_permission); 
                        $companyEm->flush();
                    }
                }

                $this->addFlash('success','User role to company has been updated successfully.');
                return $this->redirectToRoute('admin_user_edit_role_company', ['id' => $id]);
            }
            else
            {
                $this->addFlash('error','Please select modules.');
                return $this->redirectToRoute('admin_user_edit_role_company', ['id' => $id]);
            }
        }
        
        return $this->render(
            'backend/user/edit_user_role_company.html.twig',[
                'form' => $form->createView(),
                'permissions' => $permissions,
                'selected_permissions_ids' => $selected_permissions_ids,
                'company_modules' => $company_modules,
                'selected_userCompany_module_ids' => $selected_userCompany_module_ids,
            ]
        );
    }

    /**
     * @Route("/admin/user/roles-companies-delete/{id}", name="admin_user_roles_company_delete")
     */
    public function useRoleCompaniesDeleteAction($id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_user_roles_companies');
        }
        $em = $this->getDoctrine()->getManager();
        $userCompany = $em->getRepository('BackendBundle:UserCompany')->find($id);
        if($userCompany)
        {
            $company = $userCompany->getCompany();
            $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
            $companyEm = $this->getDoctrine()->getManager('company');
            $selectedPermissions = $companyEm->getRepository('CompanyBundle:UserPermission')->findBy(['userId' => $userCompany->getUser()->getId()]);

            if(count($selectedPermissions) > 0)
            {
                foreach($selectedPermissions as $sl_permsn_obj)
                {
                    $companyEm->remove($sl_permsn_obj);
                    $companyEm->flush();
                }
            }


            $em->remove($userCompany);
            $em->flush();
            $this->addFlash('delete_users_companies_success','User access deleted successfully.');
        }
        return $this->redirectToRoute('admin_user_roles_companies');
    }

    /**
     * @Route("/admin/switch-access/{id}", name="admin_user_switch_access")
     */
    public function switchAccessAction($id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_users');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);
        
        $user_company = $user->getUsersCompanies();
        if(count($user_company) > 0)
        {
            $admin = $this->getUser();
            $admin->setSwitchUser($user);
            if(count($user_company) > 1)
            {
                $em->flush();
                return $this->redirectToRoute('user_choice_company');
            }
            else
            {
                $admin->setLoginCompany($user_company[0]->getId());
                $em->flush();
                
                $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($user_company[0]->getCompany()->getCompanyDbHost(), $user_company[0]->getCompany()->getCompanyDbName(), $user_company[0]->getCompany()->getCompanyDbUser(), $user_company[0]->getCompany()->getCompanyDbPassword());

                return $this->redirectToRoute('user_dashboard');
            }
        }
        else
        {
            $this->addFlash('switch_user_error','This user has not assign to any company.');
            return $this->redirectToRoute('admin_users');
        }
    }

    /**
     * @Route("/admin/switch-admin-access", name="admin_switch_admin_access")
     */
    public function switchAdminAccessAction()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            return $this->redirectToRoute('admin_login');
        }
        else
        {
            $user = $this->getUser();
            if($user->getUserType() == 2)
            {
                return $this->redirectToRoute('user_dashboard');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $admin = $this->getUser();
        $admin->setLoginCompany(NULL);
        $admin->setSwitchUser(NULL);
        $em->flush();
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * @Route("/admin/user/company-permission-list", name="admin_user_company_permission_list")
     */
    public function companyPermissionListAction(Request $request)
    {
        $data = $request->request->all();
        $company = $this->getDoctrine()->getRepository('BackendBundle:Company')->find($data['company_id']);

        $company_modules = $company->getModules();

        $module_list_html = '';
        if(count($company_modules) > 0)
        {
            foreach ($company_modules as $module) 
            {
                $module_list_html .= '<option value="'.$module->getId().'">'.$module->getModuleName().'</option>/n';
            }
        }

        $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());

        $companyEm = $this->getDoctrine()->getManager('company');
        $permissions = $companyEm->getRepository('CompanyBundle:Permission')->findAll();

        $permission_list_html = '';
        if(count($permissions) > 0)
        {
            $permission_list_html = '<label class="col-sm-4 form-control-label required" for="user_company_form_modules">Permissions</label><div class="col-sm-4"><select id="permissions" name="permissions[]" required="required" class="form-control" multiple="multiple">';

            foreach($permissions as $permission)
            {
                $permission_list_html .= '<option value="'.$permission->getId().'">'.$permission->getPermissionName().'</option>/n';
            }
            $permission_list_html .= '</select></div>';
        }

        $data['module_list_html'] = $module_list_html;
        $data['permission_list_html'] = $permission_list_html;
        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/profile", name="my_profile")
     *@Route("/profile/")
     */
    public function myProfileAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            $is_password_form = 0;
            $user = $this->getUser();
            $form1 = $this->createForm(AccountEditForm::class, $user);
            $form2 = $this->createForm(ChangePasswordForm::class, $user);
            if($request->isMethod('POST'))
            {
                $form1->handleRequest($request);
                $form2->handleRequest($request);
                $em = $this->getDoctrine()->getManager();
                if($form1->isSubmitted() && $form1->isValid()) 
                {
                    
                    $em->flush();
                    $this->addFlash('account_edit_success','Your profile has been updated successfully.');
                    return $this->redirectToRoute('my_profile');
                }

                if($form2->isSubmitted()) 
                {
                    $is_password_form = 1;
                    if($form2->isValid())
                    {
                        $currentPassword = $form2['currentPassword']->getData();
                        $encoder_service = $this->get('security.encoder_factory');
                        $encoder = $encoder_service->getEncoder($user);
                        if($encoder->isPasswordValid($user->getPassword(), $currentPassword, $user->getSalt())) 
                        {
                            $password = $this->get('security.password_encoder')
                                            ->encodePassword($user, $user->getPlainPassword());
                            $user->setPassword($password);
                            $em->flush();
                            $this->addFlash('change_password_success','Your password has been changed successfully.');
                            return $this->redirectToRoute('my_profile');
                        }
                        else
                        {
                            $form2->get('currentPassword')->addError(new FormError('Current password does not match'));
                        }
                    }
                    /*else
                    {
                        var_dump($form2->getErrors());
                    }*/
                }
            }
            
            return $this->render('backend/user/my_profile.html.twig', [
                'form1' => $form1->createView(),
                'form2' => $form2->createView(),
                'is_password_form' => $is_password_form,
            ]);
        }
        else
        {
            return $this->redirectToRoute('admin_login');
        }
    }
}
