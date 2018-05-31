<?php

namespace BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class MainController extends Controller
{
    public function dd($var)
    {
        dump($var); exit();
    }

    /**
     * @param $data
     * @param null $ignoredAttributes
     * @return json Response
     */
    public function serializedJsonResponse($data, $ignoredAttributes = null)
    {
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });

        $normalizer->setIgnoredAttributes(['__initializer__', '__cloner__', '__isInitialized__']);
        if (is_array($ignoredAttributes)) {
            $normalizer->setIgnoredAttributes($ignoredAttributes);
        }

        $serializer = new Serializer(array($normalizer), array($encoder));

        $response = new Response(
            $serializer->serialize($data, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
         );

        return $response;
    }

    /**
     * @param $moduleName
     * @return mixed
     */
    protected function getModuleId($moduleName = '')
    {
        if ($moduleName === '') {
            throw new \Exception('You must provide module name.');
        }
        return $this->getParameter('modules')[$moduleName]['id'];
    }

    protected function checkAuthAdmin()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            return false;
        }
        else
        {
            $user = $this->getUser();
            if($user->getUserType() == 1 && $user->getSwitchUser() == NULL && $user->getLoginCompany() == NULL)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    protected function checkAuthUser($type = 'all')
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            return false;
        }
        else
        {
            $user = $this->getUser();
            if($user->getUserType() == 1 && $user->getSwitchUser() != NULL && $user->getLoginCompany() != NULL)
            {
                if($type == 'all')
                {
                    return true;
                }
                else
                {
                    $user_company_id = $user->getLoginCompany();
                    $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($user_company_id);
                    $modules = $usersCompany->getModules();
                    $module_ids = $modules->map(function($entity){
                                        return $entity->getId();
                                    })->toArray();
                    if(in_array($type, $module_ids))
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            elseif($user->getUserType() == 1 && $user->getSwitchUser() == NULL && $user->getLoginCompany() != NULL)
            {
                if($type == 'all')
                {
                    return true;
                }
                else
                {
                    $user_company_id = $user->getLoginCompany();
                    $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($user_company_id);
                    $modules = $usersCompany->getModules();
                    $module_ids = $modules->map(function($entity){
                                        return $entity->getId();
                                    })->toArray();
                    if(in_array($type, $module_ids))
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            elseif($user->getUserType() == 2 && $user->getLoginCompany() != NULL)
            {
                if($type == 'all')
                {
                    return true;
                }
                else
                {
                    $user_company_id = $user->getLoginCompany();
                    $usersCompany = $this->getDoctrine()->getRepository('BackendBundle:UserCompany')->find($user_company_id);
                    $modules = $usersCompany->getModules();
                    $module_ids = $modules->map(function($entity){
                                        return $entity->getId();
                                    })->toArray();
                    if(in_array($type, $module_ids))
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            else
            {
                return false;
            }
        }
    }

    public function privilegeUser()
    {
        $user = $this->getUser();
        if($user->getUserType() == 1 && $user->getSwitchUser() != NULL && $user->getLoginCompany() != NULL)
        {
            $user = $user->getSwitchUser();
        }
        return $user;
    }

    public function dynamicUserMenuAction()
    {
        $user = $this->getUser();
        $modules = new \Doctrine\Common\Collections\ArrayCollection();
        if($user->getLoginCompany() != NULL)
        {
            $em = $this->getDoctrine()->getManager();
            $userCompany = $em->getRepository('BackendBundle:UserCompany')->find($user->getLoginCompany());
            if(!empty($userCompany))
            {
                $modules = $userCompany->getModules();
            }
        }
        return $this->render(
            'backend/includes/user_dynamic_menu.html.twig',
            ['modules' => $modules]
        );
    }

    public function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }

}
