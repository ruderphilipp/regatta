<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class ClubType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('abbreviation')
            ->add('city')
            ->add('state', ChoiceType::class, array(
                'label' => 'Bundesland',
                'choices' => array(
                    'Baden-Württemberg' => 'Baden-Württemberg',
                    'Bayern' => 'Bayern',
                    'Berlin' => 'Berlin',
                    'Brandenburg' => 'Brandenburg',
                    'Bremen' => 'Bremen',
                    'Hamburg' => 'Hamburg',
                    'Hessen' => 'Hessen',
                    'Mecklenburg-Vorpommern' => 'Mecklenburg-Vorpommern',
                    'Niedersachsen' => 'Niedersachsen',
                    'Nordrhein-Westfalen' => 'Nordrhein-Westfalen',
                    'Rheinland-Pfalz' => 'Rheinland-Pfalz',
                    'Saarland' => 'Saarland',
                    'Sachsen' => 'Sachsen',
                    'Sachsen-Anhalt' => 'Sachsen-Anhalt',
                    'Schleswig-Holstein' => 'Schleswig-Holstein',
                    'Thüringen' => 'Thüringen',
                ),
                'expanded' => false,
                'multiple' => false,
            ))
            ->add('zip')
            ->add('street')
            ->add('streetNumber')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Club'
        ));
    }
}
