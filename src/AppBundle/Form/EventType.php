<?php

namespace AppBundle\Form;

use AppBundle\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class EventType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('eventType', ChoiceType::class, array(
                'label' => 'Typ',
                'choices' => array(
                    'regular' => null,
                    'Row&Run' => Event::TYPE_ROW_RUN,
                ),
                'expanded' => true,
                'multiple' => false,
            ))
            ->add('start', DateTimeType::class)
            ->add('end', DateTimeType::class)
            ->add('registrationStart', DateTimeType::class)
            ->add('registrationEnd', DateTimeType::class)
            ->add('representativesMeetingStart', TimeType::class)
            ->add('representativesMeetingEnd', TimeType::class)
            ->add('moreInfoWebsite', TextType::class)
            ->add('description', TextAreaType::class)
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Event'
        ));
    }
}
