<?php
// src/BackendBundle/Form/CompanyForm.php
namespace BackendBundle\Form;

use BackendBundle\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CompanyForm extends AbstractType
{
    private $existing_db;
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->existing_db = $options['existing_db'];
        $existing_db_arr = [];
        if(count($this->existing_db) > 0)
        {
            foreach($this->existing_db as $ex_db)
            {
                $existing_db_arr[$ex_db] = $ex_db;
            }
        }
        $builder
            ->add('companyName', TextType::class, ['label' => false])
            ->add('connectionName', TextType::class, ['label' => false])
            ->add('companyDbHost', TextType::class, ['label' => false, 'required' => false])
            ->add('companyDbName', TextType::class, ['label' => false])
            ->add('companyDbUser', TextType::class, ['label' => false, 'required' => false])
            ->add('companyDbPassword', TextType::class, ['label' => false, 'required' => false])
            ->add('locationType', ChoiceType::class, [
                        'choices'  => [
                            'Local' => 1,
                            'External' => 2,
                        ],
                    ]
                )
            ->add('dbType', ChoiceType::class, [
                        'choices'  => [
                            'New' => 1,
                            'Existing' => 2,
                        ],
                    ]
                )
            ->add('existingDbs', ChoiceType::class, [
                        'choices'  => $existing_db_arr,
                        'mapped' =>false,
                    ]
                )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Company::class,
            'existing_db' => [],
        ));
    }
}