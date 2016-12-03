<?php

namespace AppBundle\Form;

use AppBundle\Entity\Competitor;
use AppBundle\Entity\Race;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
        // set default values
        $default_starter_min = 2;
        $default_starter_max = 1000;
        $default_max_starter_per_section = 4;
        $default_number = $options['number'];

        /** @var Race $me */
        $me = null;
        if (isset($options['data'])) {
            $me = $options['data'];
        }
        if (!is_null($me)) {
            if (!empty($me->getStarterMin())) {
                $default_starter_min = $me->getStarterMin();
            }
            if (!empty($me->getStarterMax())) {
                $default_starter_max = $options['data']->getStarterMax();
            }
            if (!empty($me->getMaxStarterPerSection())) {
                $default_max_starter_per_section = $options['data']->getMaxStarterPerSection();
            }
            if (!empty($me->getNumberInEvent())) {
                $default_number = $options['data']->getNumberInEvent();
            }
        }

        // only if corresponding event is of type row&run and single race
        // NOTE: This can not be shown in the creation dialog since there is no decision about the team size, yet.
        if (!is_null($me) && !is_null($me->getEvent()) && $me->getEvent()->isRowAndRun() && 1 == $me->getTeamsize()) {
            $builder
                ->add('raceType', ChoiceType::class, array(
                    'label' => 'Typ',
                    'choices' => array(
                        'Running' => Race::TYPE_RUN,
                        'Indoor Rowing' => Race::TYPE_ROW,
                    ),
                    'expanded' => true,
                    'multiple' => false,
                ));
            // only if this is a "rowing" event
            // NOTE: you might have to open the "edit" dialog more than once to set this field
            if (Race::TYPE_ROW === $me->getRaceType()) {
                $choices = $me->getEvent()->getRaces()->filter(
                    function ($r) use ($me) {
                        /** @var Race $r */
                        return Race::TYPE_RUN === $r->getRaceType() &&
                            $r->getAgeMin() <= $me->getAgeMin() && $r->getAgeMax() >= $me->getAgeMax();
                    });

                $builder
                    ->add('runRace', ChoiceType::class, array(
                        'label' => 'zugehöriges Lauf-Rennen',
                        'choices' => $choices,
                        'expanded' => false,
                        'multiple' => false,
                        'choice_label' => function($race, $key, $index) {
                            /** @var Race $race */
                            return $race->__toString();
                        },
                    ));
            }
        }
        $builder
            ->add('numberInEvent', HiddenType::class, array(
                'data' => $default_number,
            ))
            ->add('gender', ChoiceType::class, array(
                'label' => 'Geschlecht',
                'choices' => array(
                    'weiblich' => Competitor::GENDER_FEMALE,
                    'männlich' => Competitor::GENDER_MALE,
                    'mixed' => Competitor::GENDER_BOTH,
                ),
                'expanded' => true,
                'multiple' => false,
                ))
            // TODO: kann abgeleitet werden und kann somit hier entfallen
            ->add('ageClass', ChoiceType::class, array(
                'label' => 'Altersklasse',
                // see <http://www.rudern.de/wettkampf/altersklassen/>
                'choices' => array(
                    'Kinder (<15)' => 'Kind',
                    'Junioren (15-18)' => 'Junior',
                    'Junioren B (15/16)' => 'Junior',
                    'Junioren A (17/18)' => 'Junior',
                    'Senioren (19-27)' => 'Senior',
                    'Senioren B (19-22)' => 'Senior',
                    'Senioren A (23-27)' => 'Senior',
                    'Masters (27+)' => 'Master',
                    'Offen' => 'Offen',
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
                    'value' => $default_starter_min,
                ),
            ))
            ->add('starterMax', IntegerType::class, array(
                'label' => 'Maximalanzahl an Startern',
                'attr' => array(
                    'min' => 1,
                    'value' => $default_starter_max,
                ),
            ))
            ->add('maxStarterPerSection', IntegerType::class, array(
                'label' => 'max. Anzahl Starter pro Gruppe',
                'attr' => array(
                    'min' => 1,
                    'value' => $default_max_starter_per_section,
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
            ->add('teamsize', IntegerType::class, array(
                'label' => 'Anzahl Sportler pro Boot/Gruppe',
                'attr' => array(
                    'min' => 1,
                )
            ))
            ->add('extraText', TextType::class, array(
                'label' => 'Zusatztext'
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Race',
            'number' => -1,
        ));
    }
}
