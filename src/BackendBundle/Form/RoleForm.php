<?php
// src/BackendBundle/Form/RoleForm.php
namespace BackendBundle\Form;

use BackendBundle\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RoleForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('roleName', TextType::class, ['label' => false])
            ->add('modules', EntityType::class, array(
                'class' => 'BackendBundle:Module',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->where('m.isActive = 1')
                        ->orderBy('m.moduleName', 'ASC');
                },
                'choice_label' => 'moduleName',
                'placeholder' => 'Choose Modules',
                'multiple' => true,
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Role::class,
        ));
    }
}