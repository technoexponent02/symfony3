<?php
/**
 * Created by PhpStorm.
 * User: tuhin
 * Date: 8/4/17
 * Time: 2:53 PM
 */

namespace BackendBundle\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPassword extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*->add('password', PasswordType::class)
            ->add('confirm_password', PasswordType::class)*/
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'required' => true
            ])
            ->add('reset', SubmitType::class)
        ;
    }
}