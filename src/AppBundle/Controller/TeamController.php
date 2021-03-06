<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Club;
use AppBundle\Entity\Competitor;
use AppBundle\Entity\Event;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Race;
use AppBundle\Entity\Team;
use AppBundle\Entity\TeamPosition;
use AppBundle\Repository\ClubRepository;
use AppBundle\Repository\MembershipRepository;
use AppBundle\Repository\RaceRepository;
use AppBundle\Repository\TeamRepository;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/team")
 */
class TeamController extends Controller
{
    /**
     * Creates a new Team entity.
     *
     * @Route("/new", name="team_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $maxTeamSize = 9;
        $minAge = 0;
        $maxAge = 100;
        $gender = Competitor::GENDER_BOTH;
        //$withCox = true;

        /** @var Race $race */
        $race = $request->get('race', null);

        if (!is_null($race)) {
            /** @var RaceRepository $raceRepo */
            $raceRepo = $em->getRepository('AppBundle:Race');
            /** @var Race|null $race */
            $race = $raceRepo->find(intval($race));
            if (!is_null($race)) {
                $maxTeamSize = $race->getTeamsize();
                $minAge = $race->getAgeMin();
                $maxAge = $race->getAgeMax();
                $gender = $race->getGender();
            }
        }

        // get all potential candidates
        /** @var MembershipRepository $memberRepo */
        $memberRepo = $em->getRepository('AppBundle:Membership');
        $memberships = $memberRepo->findAllCurrent($gender, $minAge, $maxAge);
        // sort by id (needed due to bug in udiff comparison function; otherwise the first element is not checked correctly)
        usort($memberships, function ($x, $y) {
            /** @var Membership $x */
            /** @var Membership $y */
            $a = $x->getId();
            $b = $y->getId();
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        $competitors = array();
        // filter out all those that are already in this race
        if (!is_null($race) && $race instanceof Race && isset($raceRepo)) {
            $competitors = $raceRepo->findAllCompetitors($race)->toArray();
            usort($competitors, function ($x, $y) {
                /** @var Membership $x */
                /** @var Membership $y */
                $a = $x->getId();
                $b = $y->getId();
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });
        }
        $memberships = array_udiff($memberships, $competitors, function ($a, $b) {
            /** @var Membership $a */
            /** @var Membership $b */
            if ($a->equals($b))
                return 0;
            else
                return -1;
        });

        if (0 == count($memberships)) {
            $this->addFlash(
                'error',
                'Keine passenden Sportler gefunden, um weitere neue Mannschaft anzulegen! Bitte bei den existierenden Teams nachschauen.'
            );
            return $this->redirect($request->headers->get('referer'));
        } else {
            /** @var ClubRepository $clubRepo */
            $clubRepo = $em->getRepository('AppBundle:Club');
            $clubs = $clubRepo->findAll();
            usort($clubs, function($a, $b)
            {
                /** @var Club $a */
                $left = $a->getCity() . '_' . $a->getAbbreviation();
                /** @var Club $b */
                $right = $b->getCity() . '_' . $b->getAbbreviation();
                return strcmp($left, $right);
            });
            // no model
            $data = array();
            $fb = $this->createFormBuilder($data);
            $fb->add('club', ChoiceType::class, array(
                'label' => 'Meldender Club',
                'required' => true,
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
            ));

            // team members at the positions
            for($i = 1; $i <= $maxTeamSize ; $i++) {
                $fb->add(
                    'members_'.$i,
                    ChoiceType::class, array(
                    'label' => 'Platz '.$i,
                    'required' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => $memberships,
                    'choice_label' => function($m, $key, $index) use ($gender) {
                        /** @var Membership $m */
                        /** @var Competitor $p */
                        $p = $m->getPerson();

                        $result = $p->getFirstName()
                            . ' '
                            . strtoupper($p->getLastName())
                            . ' (' . $p->getYearOfBirth()
                            . ', ' . $p->getAge();
                        if (Competitor::GENDER_BOTH == $gender) {
                            $result .= ', '.$p->getGenderSymbol();
                        }
                        $result .= ')';
                        return $result;
                    },
                    'group_by' => function($m, $key, $index) {
                        /** @var Membership $m*/
                        if (!empty(trim($m->getClub()->getShortname()))) {
                            return $m->getClub()->getShortname();
                        } else {
                            return $m->getClub()->getName();
                        }
                    },
                ));
            }
            $form = $fb->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $isGood = true;
                if (!$form->isValid()) {
                    foreach ($form->getErrors() as $error) {
                        $this->addFlash(
                            'error',
                            $error->getMessage()
                        );
                    }
                    $isGood = false;
                }

                if ($isGood) {
                    /** @var Club $club */
                    $club = $form->get('club')->getData();
                    if (is_null($club)) {
                        $this->addFlash(
                            'error',
                            'Kein Club angegeben!'
                        );
                        $isGood = false;
                    }
                }
                $team = null;
                if ($isGood) {
                    $team = new Team();
                    $team->setClub($club);
                    $team->setName($club->getName());
                    $em->persist($team);
                }

                $found[] = array();
                for($i = 1; $i <= $maxTeamSize; $i++) {
                    /** @var Membership $m */
                    $m = $form->get('members_' . $i)->getData();
                    if (is_null($m)) {
                        $this->addFlash(
                            'error',
                            sprintf("Kein Sportler an Position %d angegeben!", $i)
                        );
                        $isGood = false;
                    } else {
                        if(in_array($m->getId(), $found)) {
                            $this->addFlash(
                                'error',
                                sprintf("Sportler an Position %d mehrfach angegeben!", $i)
                            );
                            $isGood = false;
                        } else {
                            $found[] = $m->getId();
                            $posInTeam = new TeamPosition();
                            $posInTeam->setTeam($team)
                                ->setPosition($i)
                                ->setIsCox(false)
                                ->setMembership($m);
                            $em->persist($posInTeam);
                        }
                    }
                }

                if ($isGood) {
                    $em->flush();
                    $this->addFlash(
                        'notice',
                        'Mannschaft erfolgreich angelegt!'
                    );
                }
            }

            return $this->render(
                'team/new.html.twig',
                array(
                    'race' => $race,
                    'form' => $form->createView(),
                )
            );
        }
    }

    /**
     * Check-in a team.
     *
     * @Route("/{team}/checkIn", name="team_checkin")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function checkInAction(Request $request, Team $team)
    {
        if ($team->isCheckedIn()) {
            $this->addFlash(
                'error',
                'Mannschaft ist bereits eingecheckt!'
            );
            if (!is_null($request->headers->get('referer'))) {
                return $this->redirect($request->headers->get('referer'));
            } else {
                return $this->redirectToRoute('homepage');
            }
        }

        $data = array();
        $form = $this->createFormBuilder($data)
            ->add('token', TextType::class, array(
                'label' => 'Token',
            ))
            ->add('ref', HiddenType::class, array(
                'data' => $request->headers->get('referer'),
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            // $data is a simply array with your form fields
            // like "query" and "category" as defined above.
            $data = $form->getData();

            // check if the token exists for another competitor
            $em = $this->getDoctrine()->getManager();
            /** @var TeamRepository $repo */
            $repo = $em->getRepository('AppBundle:Team');
            if ($repo->isTokenExistent($data["token"])) {
                $this->addFlash(
                    'error',
                    'Token ist bereits eingecheckt!'
                );
            } else {
                // if unique, then save
                $team->setToken($data["token"]);
                $em = $this->getDoctrine()->getManager();
                $em->persist($team);
                $em->flush();
            }

            return $this->redirect($data['ref']);
        }

        return $this->render('team/checkin.html.twig', array(
            'team' => $team,
            'form' => $form->createView(),
            'previousUrl' => $request->headers->get('referer'),
        ));
    }

    /**
     * Check-out a team.
     *
     * @Route("s/checkOut/{event}", name="team_checkout")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function checkOutAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var TeamRepository $repo */
        $repo = $em->getRepository('AppBundle:Team');
        /** @var int $count */
        $count = $repo->getNumberOfCheckedInTeamsForEvent($event);

        $data = array();
        $form = $this->createFormBuilder($data)
            ->add('token', TextType::class, array(
                'label' => 'Token',
                'data' => '',
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            // $data is a simply array with your form fields
            $data = $form->getData();

            $em = $this->getDoctrine()->getManager();
            /** @var TeamRepository $repo */
            $repo = $em->getRepository('AppBundle:Team');
            if ($repo->isTokenExistent($data["token"])) {
                /** @var Team $team */
                $team = $repo->findOneBy(array('token' => $data["token"]));
                try {
                    $team->deleteToken();
                    $em->persist($team);
                    $em->flush();
                    $this->addFlash(
                        'notice',
                        'Mannschaft erfolgreich ausgeloggt!'
                    );
                    $count -= 1;
                    // TODO reset value of "token" in form (= empty!)
                } catch (\Exception $e) {
                    $this->addFlash(
                        'error',
                        $e->getMessage()
                    );
                }
            } else {
                $this->addFlash(
                    'error',
                    'Keine Mannschaft für den gegebenen Token gefunden!'
                );
            }
        }

        $result = array(
            'event' => $event,
            'count' => $count,
            'form' => $form->createView(),
        );
        if (isset($team)) {
            $result['team'] = $team;
        }
        return $this->render('team/checkout.html.twig', $result);
    }
}