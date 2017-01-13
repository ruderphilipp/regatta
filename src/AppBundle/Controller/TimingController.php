<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\RaceSection;

use AppBundle\Entity\RaceSectionStatus;
use AppBundle\Entity\Registration;
use AppBundle\Entity\Team;
use AppBundle\Entity\Timing;
use AppBundle\Repository\RegistrationRepository;
use AppBundle\Repository\TeamRepository;
use AppBundle\Twig\AppExtension;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

ini_set('date.timezone', 'Europe/Berlin');

/**
 * Timing controller.
 */
class TimingController extends Controller
{
    private function getCurrentTimestamp()
    {
        /** @var \DateTime $dt */
        // get the current microseconds
        $dt = \DateTime::createFromFormat('U.u', microtime(true));
        // replace time with the server request time
        $dt->setTimestamp($_SERVER['REQUEST_TIME']);
        // format as unix timestamp (always UTC!)
        return $dt->format('U.u');
    }

    private function getTimeWithFractionSeconds()
    {
        $myTime = $this->getCurrentTimestamp();
        // XXX: ugly hack to get the offset correct when working with timestamps (always UTC)
        $tmpTime = \DateTime::createFromFormat('U.u', $myTime);
        $offsetInSeconds = (new \DateTimeZone('Europe/Berlin'))->getOffset($tmpTime);
        $tmpTime->add(new \DateInterval('PT'.$offsetInSeconds.'S'));
        return $tmpTime->format('U.u');
    }

    /**
     * Output the current server time in seconds _in UTC_
     *
     * @Route("/api/timing/server", name="timing_server_time")
     * @Method("GET")
     */
    public function showServerTimeAction()
    {
        return new Response($this->getCurrentTimestamp());
    }

    /**
     * starts the timing for a specific race (section)
     *
     * @Route("/api/timing/start/{section}", name="timing_start")
     * @Method("POST")
     */
    public function startRaceSectionAction(RaceSection $section)
    {
        $myTime = $this->getTimeWithFractionSeconds();
        $em = $this->getDoctrine()->getManager();
        $this->markAsStarted($section, $section->getId(), $em, $myTime);

        $errors = $this->get('session')->getFlashBag()->get('error');
        if (!is_null($errors) && count($errors) > 0) {
            return $this->redirectToRoute('race_start', array(
                'event' => $section->getRace()->getEvent()->getId(),
                'race' => $section->getRace()->getId()
            ));
        } else {
            $em->flush();

            return $this->redirectToRoute('race_index', array(
                'event' => $section->getRace()->getEvent()->getId(),
            ));
        }
    }

    /**
     * starts the timing for multiple race sections
     *
     * @Route("/api/timing/start/event/{event}", name="timing_start_all")
     * @Method("POST")
     */
    public function startMultipleRaceSectionsAction(Request $request, Event $event)
    {
        $timeWithFractionSeconds = $this->getTimeWithFractionSeconds();

        $sectionIDs = $request->get('sections');
        if (is_null($sectionIDs)) {
            $this->addFlash(
                'error',
                "Keine Abteilung ausgewählt zum starten!"
            );
        } else {
            $em = $this->getDoctrine()->getManager();
            $sectionRepo = $em->getRepository('AppBundle:RaceSection');
            foreach ($sectionIDs as $sID) {
                $section = $sectionRepo->find($sID);
                $this->markAsStarted($section, $sID, $em, $timeWithFractionSeconds);
            }
            $em->flush();
        }

        return $this->redirectToRoute('race_index', array(
            'event' => $event->getId(),
        ));
    }

    /**
     * Mark a given section and all related teams as started and save the start time.
     *
     * @param RaceSection $section The section to start.
     * @param int $sID Id of the section to start.
     * @param EntityManager $em For handling the DB requests
     * @param float|string $time Unix timestamp (optional) with fractional seconds
     */
    private function markAsStarted(RaceSection $section, $sID, EntityManager $em, $time)
    {
        // sanity checks
        if (is_null($section)) {
            $this->addFlash(
                'error',
                "Abteilung mit ID {$sID} nicht gefunden!"
            );
        } elseif (!$section->isReadyToStart()) {
            $this->addFlash(
                'error',
                "Abteilung {$section->getId()} von Rennen {$section->getRace()->getNumberInEvent()} ist noch" .
                " nicht bereit zum starten!"
            );
        } else {
            // try to mark all competiting teams as started
            /** @var RegistrationRepository $registrationRepo */
            $registrationRepo = $em->getRepository('AppBundle:Registration');
            /** @var Registration $checkedIn */
            foreach ($section->getValidRegistrations() as $checkedIn) {
                if ($checkedIn->isCheckedIn()) {
                    $this->get('logger')->debug("try to set as started: {$checkedIn->getId()}");
                    $registrationRepo->setTime(
                        $checkedIn,
                        $time,
                        Registration::CHECKPOINT_START,
                        $this->get('logger')
                    );
                }
            }

            // set this section status as started
            $section->setStatus(RaceSectionStatus::STARTED);
            $em->persist($section);

            $this->addFlash(
                'notice',
                "Abteilung {$section->getNumber()} von Rennen {$section->getRace()->getNumberInEvent()} ist gestartet!"
            );
        }
    }

    /**
     * Set the timing for a specific team at a given checkpoint
     *
     * <p>Needs the following parameters in the post request:
     * <dl>
     * <dt>token</dt><dd>The team's token</dd>
     * <dt>checkpoint</dt><dd>The checkpoint's name where the team was tracked</dd>
     * </dl>
     *
     * <p>Return values:
     * <ul>
     * <li>OK (status 200) if everything worked as expected</li>
     * <li>BAD_REQUEST (status 400) if parameters missing</li>
     * <li>FORBIDDEN (status 403) if setting the time was not allowed</li>
     * <li>NOT_FOUND (status 404) if token was wrong</li>
     * </ul>
     *
     * @Route("/api/timing/checkpoint/", name="timing_checkpoint")
     * @Method("POST")
     */
    public function setCheckpointTimeAction(Request $request)
    {
        $token = $request->get('token', null);
        $checkpoint = $request->get('checkpoint', null);
        $time = $request->get('time', null);

        if (is_null($token) || false == trim($token)) { // PHP evaluates an empty string to false
            return new Response('No token!', Response::HTTP_BAD_REQUEST);
        }
        if (is_null($checkpoint) || false == trim($checkpoint)) { // PHP evaluates an empty string to false
            return new Response('Invalid checkpoint!', Response::HTTP_BAD_REQUEST);
        }
        if (is_null($time) || !is_float($time + 0)) { // see <http://php.net/manual/en/function.is-float.php#116960>
            $time = $this->getCurrentTimestamp();
        }

        $em = $this->getDoctrine()->getManager();
        /** @var TeamRepository $teamRepo */
        $teamRepo = $em->getRepository('AppBundle:Team');
        /** @var Team $team */
        $team = $teamRepo->findOneBy(array('token' => $token));
        if (is_null($team)) {
            return new Response('Invalid token!', Response::HTTP_NOT_FOUND);
        }

        /** @var RegistrationRepository $repo */
        $repo = $em->getRepository('AppBundle:Registration');
        /** @var array[Registration] $registrations */
        $registrations = $repo->findBy(array('team' => $team));
        $startedRegistrations = array();
        /** @var Registration $r */
        foreach ($registrations as $r) {
            if ($r->isStarted() && !$r->isDone()) {
                array_push($startedRegistrations, $r);
            }
        }
        if (0 == count($startedRegistrations)) {
            return new Response('No active race with this token!', Response::HTTP_NOT_FOUND);
        }

        /** @var Registration $registration */
        foreach ($startedRegistrations as $registration) {
            try {
                $repo->setTime($registration, $time, $checkpoint, $this->get('logger'));

                if ($checkpoint == Registration::CHECKPOINT_FINISH) {
                    $this->finishSectionIfAllTeamsAreDone($registration->getSection());
                }
            } catch (\InvalidArgumentException $e) {
                $this->get('logger')->debug('TimingController::setCheckpointTimeAction - '.$e->getMessage());

                return new Response($e->getMessage(), Response::HTTP_FORBIDDEN);
            }
        }

        return new Response('', Response::HTTP_OK);
    }

    /**
     * Check if all participants are cancelled or finished and if so, finish the race
     * @param RaceSection $section
     */
    private function finishSectionIfAllTeamsAreDone(RaceSection $section)
    {
        $done = 0;
        /** @var Registration $reg */
        foreach($section->getValidRegistrations() as $reg) {
            if ($reg->isDone()) {
                $done += 1;
            }
        }
        if ($done == $section->getValidRegistrations()->count()) {
            $section->setStatus(RaceSectionStatus::FINISHED);
            $em = $this->getDoctrine()->getManager();
            $em->persist($section);
            $em->flush();
        }
    }

    /**
     * @Route("/team/{registration}/abort", name="race_abort")
     * @Method("GET")
     * @TODO move to StartController
     */
    public function abortAction(Request $request, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        $registration->setAborted();
        $em->persist($registration);
        $em->flush();
        // TODO howto call functions of other controllers with keeping doctrine reference?
        $this->finishSectionIfAllTeamsAreDone($registration->getSection());

        return $this->redirect($request->headers->get('referer'));
    }

    public function singlePeriodsAction(Registration $registration)
    {
        $app = new AppExtension();
        $myTimings = array();
        /** @var string $startPoint */
        $startPoint = null;
        /** @var \DateTime $startTime */
        $startTime = null;
        /** @var Timing $timing */
        foreach ($registration->getTimings() as $timing) {
            if (!is_null($startPoint)) {
                // calculate delta
                $myTimeD = doubleval($timing->getTime()->format('U.u'));
                $startTimeD = doubleval($startTime->format('U.u'));
                $delta = abs($myTimeD - $startTimeD);
                $deltaString = $app->timeString($delta);
                $myTimings[] = sprintf('%s - %s: <strong>%s</strong>', $startPoint, $timing->getCheckpoint(), $deltaString);
            }
            $startPoint = $timing->getCheckpoint();
            $startTime = $timing->getTime();
        }
        return new Response(
            implode('<br>', $myTimings)
        );
    }
}
