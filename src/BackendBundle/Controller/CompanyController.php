<?php

namespace BackendBundle\Controller;

use BackendBundle\Controller\MainController;
use CompanyBundle\Entity\Form;
use CompanyBundle\Entity\Setting;
use Portalen\CustomerBundle\Entity\CustomerAddressType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackendBundle\Entity\Company;
use BackendBundle\Form\CompanyForm;
use CompanyBundle\Entity\CompanyDetails;
use CompanyBundle\Entity\Permission;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\FormError;

class CompanyController extends MainController
{
    /**
     * @Route("/admin/company", name="admin_companies")
     */
    public function indexAction(Request $request)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        $repository = $this->getDoctrine()
            ->getRepository('BackendBundle:Company');
        
        //$companies = $repository->findBy(['isActive' => 1]);
        $companies = $repository->findAll();
        // replace this example code with whatever you need
        return $this->render('backend/company/list.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
            'companies' => $companies,
        ]);
    }
    /**
     * @Route("/admin/company/create", name="admin_company_create")
     */
    public function createAction(Request $request)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }

        $existing_db = $this->getParameter('existing_dbs');
        $company = new Company();
        $form = $this->createForm(CompanyForm::class, $company, ['existing_db' => $existing_db]);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $flag = 1;
            $form_data = $request->request->all();
            if($company->getLocationType() == 1)
            {
                if($company->getDbType() == 2)
                {
                    $company->setCompanyDbName($form_data['company_form']['existingDbs']);
                }
                $db_host = $this->getParameter('database_host');
                $db_user = $this->getParameter('database_user');
                $db_pass = $this->getParameter('database_password');
            }
            else
            {
                $db_host = $company->getCompanyDbHost();
                $db_user = $company->getCompanyDbUser();
                $db_pass = $company->getCompanyDbPassword();

                if(!$db_host)
                {
                    $flag = 0;
                    $form->get('companyDbHost')->addError(new FormError('DB host cannot be blank'));
                }
                if(!$db_user)
                {
                    $flag = 0;
                    $form->get('companyDbUser')->addError(new FormError('DB User cannot be blank'));
                }

            }
            if($flag == 1)
            {
                try
                {
                    $dbh = new \PDO("mysql:host=".$db_host, $db_user, $db_pass);
                    $sql = 'CREATE DATABASE IF NOT EXISTS ' . $company->getCompanyDbName() . ' CHARACTER SET utf8 COLLATE utf8_unicode_ci';
                    $dbh->exec($sql);
                    $sql2 = 'SHOW DATABASES LIKE "' . $company->getCompanyDbName() . '"';

                    $stmt = $dbh->prepare($sql2);
                    $stmt->execute();
                    $results = $stmt->fetch();
                    if(!empty($results))
                    {
                        $company->setCompanyDbHost($db_host);
                        $company->setCompanyDbUser($db_user);
                        $company->setCompanyDbPassword($db_pass);
                        $em = $this->getDoctrine()->getManager();
                        $modules = $this->getDoctrine()->getRepository('BackendBundle:Module')->findBy(['isActive' => 1], ['id' => 'ASC']);

                        if(count($modules) > 0)
                        {
                            foreach($modules as $mod)
                            {
                                $company->addModule($mod);
                            }
                        }
                        $em->persist($company);
                        $em->flush();

                        

                        $this->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                        $application = new Application($this->get('kernel'));
                        $application->setAutoExit(false);
                        $input = new ArrayInput(array('command' => 'doctrine:schema:update', '--force' => true, '--em' => 'company'));

                        $output = new BufferedOutput();
                        $application->run($input, $output);
                        $content = $output->fetch();
                        //print_r($content);exit;
                        $companyDetails = new CompanyDetails();
                        $companyDetails->setCompanyEmail('test@gmail.com');
                        $companyDetails->setCompanyName($company->getCompanyName());

                        $companyEm = $this->getDoctrine()->getManager('company');
                        $companyEm->persist($companyDetails);
                        $companyEm->flush();

                        $permission_arr = $this->getParameter('permissions');
                        if(!empty($permission_arr))
                        {
                            foreach ($permission_arr as $prm)
                            {
                                $permission = $companyEm->getRepository('CompanyBundle:Permission')->findOneBy(['id' => $prm['id'], 'permissionName' => $prm['name']]);
                                if(empty($permission))
                                {
                                    $permission = new Permission();
                                    $permission->setId($prm['id']);
                                    $permission->setPermissionName($prm['name']);
                                    $companyEm->persist($permission);
                                }
                            }
                        }

                        // Populate form table
                        $forms = $this->getParameter('forms');
                        if(!empty($forms)) {
                            foreach ($forms as $value) 
                            {
                                $form = $companyEm->getRepository('CompanyBundle:Form')->findOneBy(['name' => $value['name'], 'moduleId' => $value['module_id']]);
                                if(empty($form))
                                {
                                    $form = new Form();
                                    $form->setName($value['name']);
                                    $form->setModuleId($value['module_id']);
                                    $companyEm->persist($form);
                                }
                            }
                        }

                        // Populate customer_address_type table
                        $customer_address_types = $this->getParameter('customer_address_type');

                        if(!empty($customer_address_types)) 
                        {
                            foreach ($customer_address_types as $value) 
                            {
                                $address_type = $companyEm->getRepository('PortalenCustomerBundle:CustomerAddressType')->findOneBy(['name' => $value['name'], 'value' => $value['value']]);
                                if(empty($address_type))
                                {
                                    $address_type = new CustomerAddressType();
                                    $address_type->setName($value['name']);
                                    $address_type->setValue($value['value']);
                                    $companyEm->persist($address_type);
                                }
                            }
                        }

                        $settings = $this->getParameter('settings');
                        if(!empty($settings)) {
                            foreach ($settings as $value) 
                            {
                                $setting = $companyEm->getRepository('CompanyBundle:Setting')->findOneBy(['keyName' => $value['key_name'], 'showName' => $value['show_name'], 'belongsTo' => $value['belongs_to'], 'fieldType' => $value['field_type']]);
                                if(empty($setting))
                                {
                                    $setting = new Setting();
                                    $setting->setKeyName($value['key_name']);
                                    $setting->setShowName($value['show_name']);
                                    $setting->setBelongsTo($value['belongs_to']);
                                    $setting->setFieldType($value['field_type']);
                                    if(!empty($value['value'])) {
                                        $setting->setValue($value['value']);
                                    }
                                    $companyEm->persist($setting);
                                }
                            }
                        }
                        // Reflect the database.
                        $companyEm->flush();

                        $this->addFlash('success', 'Company has been created successfully.');

                        return $this->redirectToRoute('admin_company_create');
                    }
                    else
                    {
                        $form->get('companyDbName')->addError(new FormError('Database cannot created due to internal server error!'));
                    }
                }
                catch(\PDOException $e)
                {
                    $form->get('companyDbHost')->addError(new FormError($e->getMessage()));
                }
            }
        }
        
        return $this->render(
            'backend/company/create.html.twig',
            array('form' => $form->createView())
        );
    }
    /**
     * @Route("/admin/company/edit/{id}", name="admin_company_edit")
     */
    public function editAction(Request $request, $id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_companies');
        }
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('BackendBundle:Company')->find($id);
        $form = $this->createForm(CompanyForm::class, $company);

        $form->handleRequest($request);
        if ($request->getMethod() == 'POST')
        {
            if ($form->isSubmitted() && $form->isValid())
            {
                $em->flush();
                $this->addFlash('success','Company has been updated successfully.');
                return $this->redirectToRoute('admin_company_edit',['id' => $id]);
            }
        }

        return $this->render(
            'backend/company/edit.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * @Route("/admin/company/delete/{id}", name="admin_company_delete")
     */
    public function deleteAction($id = NULL)
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        if($id == NULL)
        {
            return $this->redirectToRoute('admin_companies');
        }
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('BackendBundle:Company')->find($id);
        if($company)
        {
            $msg = 'Company has been archived successfully.';
            $user_companies = $company->getUsersCompanies(true);
            if($company->getIsActive() == 0)
            {
                $msg = 'Company has been activated successfully.';
                $company->setIsActive(1);
                $em->flush();

                if(count($user_companies) > 0)
                {
                    foreach($user_companies as $usr_comp)
                    {
                        $usr_comp->setIsActive(1);
                        $em->flush();
                    }
                }
            }
            else
            {
                $company->setIsActive(0);
                $em->flush();

                if(count($user_companies) > 0)
                {
                    foreach($user_companies as $usr_comp)
                    {
                        $usr_comp->setIsActive(0);
                        $em->flush();
                    }
                }
            }
            $this->addFlash('delete_company_success', $msg);
        }
        return $this->redirectToRoute('admin_companies');
    }


    /**
     * @Route("/admin/company/modules", name="admin_company_modules")
     */
    public function ModulesCompaniesAction()
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }

        $repository = $this->getDoctrine()
            ->getRepository('BackendBundle:Company');
        
        $companies = $repository->findBy(['isActive' => 1]);

        return $this->render('backend/company/module_complany_list.html.twig', [
            'companies' => $companies,
        ]);
    }

    /**
     * @Route("/admin/company/edit-company-module/{id}", name="admin_company_edit_module")
     */
    public function editCompanyModuleAction(Request $request, $id = NULL)
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
        $company = $em->getRepository('BackendBundle:Company')->find($id);
        $form = $this->createFormBuilder($company)
            ->add('modules', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'BackendBundle:Module',
                    'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
                        return $er->createQueryBuilder('m')
                            ->where('m.isActive = 1')
                            ->orderBy('m.moduleName', 'ASC');
                    },
                    'choice_label' => 'moduleName',
                    'placeholder' => 'Choose Modules',
                    'multiple' => true,
                ))
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $modules = $company->getModules();
            $module_ids = [0];
            if(count($modules) > 0)
            {
                foreach ($modules as $mod) 
                {
                    $module_ids[] = $mod->getId();
                }
            }
            $user_companies = $company->getUsersCompanies(true);
            if(count($user_companies) > 0)
            {
                foreach($user_companies as $userComp)
                {
                    $user_modules = $userComp->getModules();
                    if(count($user_modules) > 0)
                    {
                        foreach($user_modules as $user_mod)
                        {
                            if(!in_array($user_mod->getId(), $module_ids))
                            {
                                $userComp->removeModule($user_mod);
                            }
                        }
                    }
                    $user_modules_remaining = $userComp->getModules();
                    if(count($user_modules_remaining) == 0)
                    {
                        $em->remove($userComp);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success','Modules for this company has been updated successfully.');
            return $this->redirectToRoute('admin_company_edit_module', ['id' => $id]);
        }
        
        return $this->render(
            'backend/company/edit_module_company.html.twig',[
                'form' => $form->createView(),
                'company' => $company,
            ]
        );
    }

}