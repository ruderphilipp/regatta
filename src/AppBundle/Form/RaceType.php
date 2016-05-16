<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;



class RaceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gender', ChoiceType::class, array(
                'label' => 'Geschlecht',
                'choices' => array(
                    'weiblich' => 'w',
                    'männlich' => 'm',
                    'mixed' => 'a',
                ),
                'expanded' => true,
                'multiple' => false,
                ))
            ->add('ageClass', ChoiceType::class, array(
                'label' => 'Altersklasse',
                'choices' => array(
                    'Kinder (<15)' => 'Kind',
                    'Junioren (16-18)' => 'Junior',
                    'Junioren A (15/16)' => 'Junior',
                    'Junioren B (17/18)' => 'Junior',
                    'Senioren (19-27)' => 'Senior',
                    'Senioren A (19-22)' => 'Senior',
                    'Senioren B (23-27)' => 'Senior',
                    'Masters (27+)' => 'Master',
                ),
                'expanded' => false,
                'multiple' => false,
            ))
            ->add('ageMin', IntegerType::class, array(
                'label' => 'Mindestalter',
                'attr' => array(
                    'min' => 1,
                    'max' => 99,
                ),
            ))
            ->add('ageMax', IntegerType::class, array(
                'label' => 'Maximalalter',
                'attr' => array(
                    'min' => 1,
                    'max' => 99,
                ),
            ))
            ->add('level', ChoiceType::class, array(
                'label' => 'Leistungsklasse',
                'choices' => array(
                    'I' => 1,
                    'II' => 2,
                    'III' => 3,
                    'offen' => -1,
                ),
            ))
            ->add('starterMin', IntegerType::class, array(
                'label' => 'Mindestanzahl an Startern',
                'attr' => array(
                    'min' => 1,
                    'value' => 2,
                ),
            ))
            ->add('starterMax', IntegerType::class, array(
                'label' => 'Maximalanzahl an Startern',
                'attr' => array(
                    'min' => 1,
                    'value' => 10000,
                ),
            ))
            ->add('pricePerStarter', NumberType::class, array(
                'label' => 'Preis pro Starter in Euro',
                'attr' => array(
                    'min' => 0,
                    'step' => '0.10',
                    'pattern' => '^(\d+)*(\,\d+|)$',
                ),
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Race'
        ));
    }
}
