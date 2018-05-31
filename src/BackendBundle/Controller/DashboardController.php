<?php

namespace BackendBundle\Controller;

use BackendBundle\Controller\MainController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackendBundle\Entity\User;

class DashboardController extends MainController
{
    /**
     * @Route("user/dashboard", name="user_dashboard")
     */
    public function indexAction()
    {
        if($this->checkAuthUser() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        // replace this example code with whatever you need
        return $this->render('backend/dashboard/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
    /**
     * @Route("user/company-settings", name="user_company_settings")
     */
    public function companySettingsAction()
    {
        if($this->checkAuthUser() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        // replace this example code with whatever you need
        $user = $this->privilegeUser();
        $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($this->getUser()->getLoginCompany());
        $company = $usersCompany->getCompany();

        $companyEm = $this->getDoctrine()->getManager('company');
        $companyDetails = $companyEm->getRepository('CompanyBundle:CompanyDetails')->find(1);
        return $this->render('backend/dashboard/company_settings.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
            'user' => $user,
            'companyDetails' => $companyDetails,
            'company' => $company,
        ]);
    }
}
