<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class CompetitorType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentYear = getdate()["year"];
        $builder
            ->add('firstName', TextType::class, array(
                'label' => 'Vorname',
            ))
            ->add('lastName', TextType::class, array(
                'label' => 'Nachname',
            ))
            ->add('yearOfBirth', IntegerType::class, array(
                'label' => 'Geburtsjahr',
                'attr' => array(
                    'min' => $currentYear - 110,
                    'max' => $currentYear - 5,
                ),
            ))
            ->add('gender', ChoiceType::class, array(
                'label' => 'Geschlecht',
                'choices' => array(
                    'weiblic' => 'w',
                    'männlich' => 'm',
                ),
                'expanded' => true,
                'multiple' => false,
            ))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Competitor'
        ));
    }
}
