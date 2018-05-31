<?php
namespace BackendBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DatabaseSchemaUpdateListner
{
    private $container;
    private $em;
    private $companyEm;
    private $db_version;

    public function __construct(Container $container, EntityManager $em, EntityManager $companyEm, $db_version = NULL)
    {
        $this->container = $container;
        $this->em = $em;
        $this->companyEm = $companyEm;
        $this->db_version = $db_version;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }
        $database_version = $this->em->getRepository('BackendBundle:DatabaseVersion')->findOneBy(['version' => $this->db_version]);
        if(empty($database_version))
        {
            $application = new Application($this->container->get('kernel'));
            $application->setAutoExit(false);

            $input = new ArrayInput(array('command' => 'doctrine:schema:update', '--force' => true));
            $output = new BufferedOutput();
            $application->run($input, $output);
            //$content = $output->fetch();

            $database_version = new \BackendBundle\Entity\DatabaseVersion;
            $database_version->setVersion($this->db_version);
            $this->em->persist($database_version);
            $this->em->flush();

            $companies = $this->em->getRepository('BackendBundle:Company')->findAll();
            if(count($companies) > 0)
            {
                /*echo $this->container->getParameter('database_host');*/
                foreach ($companies as $company) 
                {
                    $flag = 0;
                    try
                    {
                        $dbh = new \PDO("mysql:host=".$company->getCompanyDbHost(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());
                        
                        $sql1 = 'SHOW DATABASES LIKE "' . $company->getCompanyDbName() . '"';

                        $stmt = $dbh->prepare($sql1);
                        $stmt->execute();
                        $results = $stmt->fetch();
                        if(empty($results))
                        {
                            $sql = 'CREATE DATABASE IF NOT EXISTS ' . $company->getCompanyDbName() . ' CHARACTER SET utf8 COLLATE utf8_unicode_ci';
                            $dbh->exec($sql);
                            $sql2 = 'SHOW DATABASES LIKE "' . $company->getCompanyDbName() . '"';

                            $stmt = $dbh->prepare($sql2);
                            $stmt->execute();
                            $results = $stmt->fetch();
                            if(empty(!$results))
                            {
                                $flag = 1;
                            }
                        }
                        else
                        {
                            $flag = 1;
                        }

                        if($flag == 1)
                        {
                            $this->container->get(sprintf('doctrine.dbal.%s_connection', 'company'))->forceSwitch($company->getCompanyDbHost(), $company->getCompanyDbName(), $company->getCompanyDbUser(), $company->getCompanyDbPassword());

                            $input2 = new ArrayInput(array('command' => 'doctrine:schema:update', '--force' => true, '--em' => 'company'));

                            $output2 = new BufferedOutput();
                            $application->run($input2, $output2);
                            $content = $output->fetch();
                            //print_r($content);exit;

                            $companyEm = $this->companyEm;

                            $companyDetails = $companyEm->getRepository('CompanyBundle:CompanyDetails')->find(1);
                            if(empty($companyDetails))
                            {
                                $companyDetails = new \CompanyBundle\Entity\CompanyDetails();
                                $companyDetails->setCompanyEmail('test@gmail.com');
                                $companyDetails->setCompanyName($company->getCompanyName());

                                $companyEm->persist($companyDetails);
                                $companyEm->flush();
                            }
                            

                            $permission_arr = $this->container->getParameter('permissions');
                            if(!empty($permission_arr))
                            {
                                foreach ($permission_arr as $prm)
                                {
                                    $permission = $companyEm->getRepository('CompanyBundle:Permission')->findOneBy(['id' => $prm['id'], 'permissionName' => $prm['name']]);
                                    if(empty($permission))
                                    {
                                        $permission = new \CompanyBundle\Entity\Permission();
                                        $permission->setId($prm['id']);
                                        $permission->setPermissionName($prm['name']);
                                        $companyEm->persist($permission);
                                    }
                                }
                            }

                            // Populate form table
                            $forms = $this->container->getParameter('forms');
                            if(!empty($forms)) {
                                foreach ($forms as $value) 
                                {
                                    $form = $companyEm->getRepository('CompanyBundle:Form')->findOneBy(['name' => $value['name'], 'moduleId' => $value['module_id']]);
                                    if(empty($form))
                                    {
                                        $form = new \CompanyBundle\Entity\Form();
                                        $form->setName($value['name']);
                                        $form->setModuleId($value['module_id']);
                                        $companyEm->persist($form);
                                    }
                                }
                            }

                            // Populate customer_address_type table
                            $customer_address_types = $this->container->getParameter('customer_address_type');

                            if(!empty($customer_address_types)) 
                            {
                                foreach ($customer_address_types as $value) 
                                {
                                    $address_type = $companyEm->getRepository('CompanyBundle:CustomerAddressType')->findOneBy(['name' => $value['name'], 'value' => $value['value']]);
                                    if(empty($address_type))
                                    {
                                        $address_type = new \CompanyBundle\Entity\CustomerAddressType();
                                        $address_type->setName($value['name']);
                                        $address_type->setValue($value['value']);
                                        $companyEm->persist($address_type);
                                    }
                                }
                            }

                            $settings = $this->container->getParameter('settings');
                            if(!empty($settings)) {
                                foreach ($settings as $value) 
                                {
                                    $setting = $companyEm->getRepository('CompanyBundle:Setting')->findOneBy(['keyName' => $value['key_name'], 'showName' => $value['show_name'], 'belongsTo' => $value['belongs_to'], 'fieldType' => $value['field_type']]);
                                    if(empty($setting))
                                    {
                                        $setting = new \CompanyBundle\Entity\Setting();
                                        $setting->setKeyName($value['key_name']);
                                        $setting->setShowName($value['show_name']);
                                        $setting->setBelongsTo($value['belongs_to']);
                                        $setting->setFieldType($value['field_type']);
                                        $companyEm->persist($setting);
                                    }
                                }
                            }
                            // Reflect the database.
                            $companyEm->flush();
                        }

                    }
                    catch(\PDOException $e)
                    {
                        /*echo $e->getMessage();exit;*/
                    }
                }
            }

            $input3 = new ArrayInput(array('command' => 'cache:clear', '--env' => 'prod'));
            $output3 = new BufferedOutput();
            $application->run($input3, $output3);

            $input4 = new ArrayInput(array('command' => 'cache:clear', '--env' => 'dev'));
            $output4 = new BufferedOutput();
            $application->run($input4, $output4);
        }
        else
        {
            return;
        }
            
    }

}