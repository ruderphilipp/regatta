<?php

namespace AppBundle\Form;

use AppBundle\Entity\Registration;
use AppBundle\Entity\Team;
use AppBundle\Entity\TeamPosition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class RegistrationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['teams']) || is_null($options['teams'])) {
            $teams = array();
        } else {
            $teams = $options['teams'];
            usort($teams, function($a, $b)
            {
                /** @var Team $a */
                $left = $a->getClub()->getName() . '_' . implode('#', $a->getMembers()->map(function($pos) {
                       /** @var TeamPosition $pos */
                        return $pos->getMembership()->getPerson()->getLastName();
                    })->toArray());
                /** @var Team $b */
                $right = $b->getClub()->getName() . '_' . implode('#', $b->getMembers()->map(function($pos) {
                        /** @var TeamPosition $pos */
                        return $pos->getMembership()->getPerson()->getLastName();
                    })->toArray());
                return strcmp($left, $right);
            });
        }

        $builder
            ->add('lane', HiddenType::class, array(
                'required' => false,
            ))
            ->add('team', ChoiceType::class, array(
                'label' => 'Mannschaft',
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => $teams,
                'choice_label' => function($team, $key, $index) {
                    /** @var Team $team */
                    return implode(', ', $team->getMembers()->map(function($pos) {
                        /** @var TeamPosition $pos */
                        return
                            $pos->getMembership()->getPerson()->getFirstName()
                            .' '
                            .strtoupper($pos->getMembership()->getPerson()->getLastName());
                    })->toArray());
                },
                'group_by' => function($team, $key, $index) {
                    /** @var Team $team */
                    if (!empty(trim($team->getClub()->getShortname()))) {
                        return $team->getClub()->getShortname();
                    } else {
                        return $team->getClub()->getName();
                    }
                },
            ))
            ->add('section', HiddenType::class, array(
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
            'data_class' => 'AppBundle\Entity\Registration',
            'teams' => null,
        ));
    }
}
