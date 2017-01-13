<?php

namespace AppBundle\Form;

use AppBundle\Entity\Club;
use AppBundle\Entity\Competitor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MembershipType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Competitor $competitor */
        $competitor = $options['competitor'];
        $min_year = $competitor->getYearOfBirth();
        $max_year = getdate()['year'];

        $clubs = $options['clubs'];
        usort($clubs, function($a, $b)
        {
            /** @var Club $a */
            $left = $a->getCity() . '_' . $a->getAbbreviation();
            /** @var Club $b */
            $right = $b->getCity() . '_' . $b->getAbbreviation();
            return strcmp($left, $right);
        });

        $builder
            ->add('person', HiddenType::class, array(
                'data' => $competitor->getId(),
            ))
            ->add('club', ChoiceType::class, array(
                'label' => 'Club',
                'expanded' => false,
                'multiple' => false,
                'choices' => $clubs,
                'choice_label' => function($club, $key, $index) {
                    /** @var Club $club */
                    return $club->getName() . ' (' . $club->getCity() . ')';
                },
                'group_by' => function($club, $key, $index) {
                    /** @var Club $club */
                    return substr($club->getCity(), 0, 1);
                },
            ))
            ->add('since', BirthdayType::class, array(
                'label' => 'Beitrittsdatum',
                //'widget' => 'single_text',
                'placeholder' => array(
                    'year' => 'Jahr', 'month' => 'Monat', 'day' => 'Tag',
                ),
                'years' => range($min_year, $max_year),
            ))
            ->add('until', BirthdayType::class, array(
                'label' => 'Austrittsdatum',
                //'widget' => 'single_text',
                'placeholder' => array(
                    'year' => 'Jahr', 'month' => 'Monat', 'day' => 'Tag',
                ),
                'years' => range($min_year, $max_year),
                'required' => false,
            ))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Membership',
            'competitor' => null,
            'clubs' => null,
        ));
    }
}
