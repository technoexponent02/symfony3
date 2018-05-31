<?php
// src/BackendBundle/Form/UserCompanyForm.php
namespace BackendBundle\Form;

use BackendBundle\Entity\UserCompany;
use BackendBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;

class UserCompanyForm extends AbstractType
{
    public $privilage_user;
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->privilage_user = $options['privilage_user'];
        $builder
            ->add('user', EntityType::class, array(
                'class' => 'BackendBundle:User',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        /*->where('u.id != ' . $this->privilage_user->getId())*/
                        ->andWhere('u.isActive = 1')
                        ->orderBy('u.username', 'ASC');
                },
                'choice_label' => 'username',
                'placeholder' => 'Choose User',
            ))
            ->add('company', EntityType::class, array(
                'class' => 'BackendBundle:Company',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isActive = 1')
                        ->orderBy('c.companyName', 'ASC');
                },
                'choice_label' => 'companyName',
                'placeholder' => 'Choose Company',
            ))
            /*->add('modules', EntityType::class, array(
                'class' => 'BackendBundle:Module',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->where('m.isActive = 1')
                        ->orderBy('m.moduleName', 'ASC');
                },
                'choice_label' => 'moduleName',
                'placeholder' => 'Choose Modules',
                'multiple' => true,
            ))*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UserCompany::class,
            'privilage_user' => null,
        ));
    }
}