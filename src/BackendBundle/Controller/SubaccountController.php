<?php

namespace BackendBundle\Controller;

use BackendBundle\Controller\MainController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackendBundle\Entity\User;
use BackendBundle\Form\SubaccountForm;
use BackendBundle\Form\SubaccountEditForm;
use CompanyBundle\Entity\UserPermission;
use BackendBundle\Entity\UserCompany;

class SubaccountController extends MainController
{
    /**
     * @Route("/user/sub-account", name="user_subaccount")
     */
    public function indexAction(Request $request)
    {
        if($this->checkAuthUser(1) == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        $privilegeUser = $this->privilegeUser();
        $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($this->getUser()->getLoginCompany());
        $company = $usersCompany->getCompany();
        
        $users = $this->getDoctrine()->getRepository('BackendBundle:User')->getSubaccounts($privilegeUser, $company);
        // replace this example code with whatever you need
        return $this->render('backend/subaccount/list.html.twig', [
            'users' => $users,
            'company' => $company,
        ]);
    }
    /**
     * @Route("/user/sub-account/create", name="user_subaccount_create")
     */
    public function createAction(Request $request)
    {
        if($this->checkAuthUser(1) == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        $privilegeUser = $this->privilegeUser();
        $repository = $this->getDoctrine()->getRepository('BackendBundle:UserCompany');
        $privilegeUsersCompany = $repository->find($this->getUser()->getLoginCompany());
        $company = $privilegeUsersCompany->getCompany();
        $company_modules = $privilegeUsersCompany->getModules();
        $selected_userCompany_module_ids = [0];

        $companyEm = $this->getDoctrine()->getManager('company');
        $permissions = $companyEm->getRepository('CompanyBundle:Permission')->findAll();
        $selected_permissions_ids = [0];

        $user = new User();
        $form = $this->createForm(SubaccountForm::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $form_data = $request->request->all();
            if(!empty($form_data['modules_ids']))
            {
                $newUser = new User();
                $newUser->setEmail($user->getEmail());
                $newUser->setUsername($user->getUsername());
                $password = $this->get('security.password_encoder')
                    ->encodePassword($user, $user->getPlainPassword());
                $newUser->setPassword($password);
                $newUser->setUserType(2);
                $em = $this->getDoctrine()->getManager();
                $em->persist($newUser); 
                $em->flush();


                $userCompany = new UserCompany();
                $userCompany->setUser($newUser);
                $userCompany->setCompany($company);

                foreach ($company_modules as $module) 
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
                    $companyEm = $this->getDoctrine()->getManager('company');
                    foreach($form_data['permissions'] as $permission_id) 
                    {
                        $permission = $companyEm->getRepository('CompanyBundle:Permission')->find($permission_id);
                        $user_permission = new UserPermission();
                        $user_permission->setUserId($newUser->getId());
                        $user_permission->setPermission($permission);
                        $companyEm->persist($user_permission); 
                        $companyEm->flush();
                    }
                }
                
                $this->addFlash('success','Sub account has been created successfully.');
                return $this->redirectToRoute('user_subaccount_create');
            }
            else
            {
                $this->addFlash('error','Please select modules.');
                return $this->redirectToRoute('user_subaccount_create');
            }
        }
        return $this->render(
            'backend/subaccount/create.html.twig',[
                'form' => $form->createView(),
                'permissions' => $permissions,
                'selected_permissions_ids' => $selected_permissions_ids,
                'company_modules' => $company_modules,
                'selected_userCompany_module_ids' => $selected_userCompany_module_ids,
            ]
        );
    }

    /**
     * @Route("/user/sub-account/edit/{id}", name="user_subaccount_edit")
     */
    public function editAction(Request $request, $id = NULL)
    {
        if($this->checkAuthUser(1) == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('user_subaccount');
        }

        $privilegeUser = $this->privilegeUser();
        $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($this->getUser()->getLoginCompany());
        $company = $usersCompany->getCompany();

        $company_modules = $usersCompany->getModules();
        $selected_userCompany_module_ids = [0];

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);

        $editUserCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->findOneBy(['user' => $user, 'company' => $company]);

        $old_userCompany_modules = $editUserCompany->getModules();
        
        if(count($old_userCompany_modules) > 0)
        {
            foreach($old_userCompany_modules as $old_module)
            {
                $selected_userCompany_module_ids[] = $old_module->getId();
            }
        }


        $companyEm = $this->getDoctrine()->getManager('company');
        $permissions = $companyEm->getRepository('CompanyBundle:Permission')->findAll();
        $oldSelectedPermissions = $companyEm->getRepository('CompanyBundle:UserPermission')->findBy(['userId' => $user->getId()]);
       
        $selected_permissions_ids = [0];
        if(count($oldSelectedPermissions) > 0)
        {
            foreach($oldSelectedPermissions as $sl_permsn)
            {
                $selected_permissions_ids[] = $sl_permsn->getPermission()->getId();
            }
        }

        $form = $this->createForm(SubaccountEditForm::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $form_data = $request->request->all();
            if(!empty($form_data['modules_ids']))
            {
              
                if(count($oldSelectedPermissions) > 0)
                {
                    foreach($oldSelectedPermissions as $sl_permsn_obj)
                    {
                        $companyEm->remove($sl_permsn_obj);
                        $companyEm->flush();
                    }
                }



                if(count($old_userCompany_modules) > 0)
                {
                    foreach($old_userCompany_modules as $old_userCompany_module)
                    {
                        $editUserCompany->removeModule($old_userCompany_module);
                    }
                }

                foreach($usersCompany->getModules() as $module) 
                {
                    if(in_array($module->getId(), $form_data['modules_ids']))
                    {
                        $editUserCompany->addModule($module);
                    }
                }

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                if(!empty($form_data['permissions']))
                {
                    foreach($form_data['permissions'] as $permission_id) 
                    {
                        $permission = $companyEm->getRepository('CompanyBundle:Permission')->find($permission_id);
                        $user_permission = new UserPermission();
                        $user_permission->setUserId($user->getId());
                        $user_permission->setPermission($permission);
                        $companyEm->persist($user_permission); 
                        $companyEm->flush();
                    }
                }
                
                 $this->addFlash('success','Sub account has been updated successfully.');
                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user 
                return $this->redirectToRoute('user_subaccount_edit', ['id' => $id]);
            }
            else
            {
                $this->addFlash('error','Please select modules.');
                 return $this->redirectToRoute('user_subaccount_edit', ['id' => $id]);
            }
        }
        return $this->render(
            'backend/subaccount/edit.html.twig',[
                'form' => $form->createView(),
                'company' => $company,
                'permissions' => $permissions,
                'selected_permissions_ids' => $selected_permissions_ids,
                'company_modules' => $company_modules,
                'selected_userCompany_module_ids' => $selected_userCompany_module_ids,
            ]
        );
    }
    
    /**
     * @Route("/user/sub-account/delete/{id}", name="user_subaccount_delete")
     */
    public function deleteAction($id = NULL)
    {
        if($this->checkAuthUser(1) == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('user_subaccount');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->find($id);
        if($user)
        {
            $user_name = $user->getUsername();
            $user_name .= '-archived'.$user->getId();
            $user_email = $user->getEmail();
            $user_email .= '-archived'.$user->getId();
            $user->setUsername($user_name);
            $user->setEmail($user_email);
            $user->setIsActive(0);
            $em->flush();
            $this->addFlash('delete_user_success','User sub account has been archived successfully.');
        }
        return $this->redirectToRoute('user_subaccount');
    }
}
