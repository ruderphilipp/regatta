<?php

namespace AppBundle\Controller;

use AppBundle\Entity\RaceSection;

use AppBundle\Entity\Registration;
use AppBundle\Repository\RegistrationRepository;
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
        $dt = \DateTime::createFromFormat('U', $_SERVER['REQUEST_TIME']);
        $dt->setTimezone(new \DateTimeZone('Europe/Berlin'));
        return $dt->getTimestamp();
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
        $myTime = $this->getCurrentTimestamp();

        // sanity checks
        if (is_null($section)) {
            throw new \InvalidArgumentException('How should I start a NULL section?');
        } elseif (!$section->isReadyToStart()) {
            $this->addFlash(
                'error',
                "Abteilung {$section->getId()} von Rennen {$section->getRace()->getNumberInEvent()} ist noch" .
                " nicht bereit zum starten!"
            );

            return $this->redirectToRoute('race_start', array(
                'event' => $section->getRace()->getEvent()->getId(),
                'race' => $section->getRace()->getId()
            ));
        }

        $em = $this->getDoctrine()->getManager();
        /** @var RegistrationRepository $repo */
        $repo = $em->getRepository('AppBundle:Registration');
        /** @var Registration $checkedIn */
        foreach($section->getValidRegistrations() as $checkedIn) {
            if ($checkedIn->isCheckedIn()) {
                $this->get('logger')->debug("try to set as started: {$checkedIn->getId()}");
                $repo->setTime($checkedIn, $myTime, Registration::CHECKPOINT_START, $this->get('logger'));
            }
        }

        return new Response(
            "Section {$section->getId()} of race {$section->getRace()->getNumberInEvent()} started successfully!",
            Response::HTTP_OK);
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
        $time = $this->getCurrentTimestamp();

        $token = $request->get('token', null);
        $checkpoint = $request->get('checkpoint', null);

        if (is_null($token) || false == trim($token)) { // PHP evaluates an empty string to false
            return new Response('No token!', Response::HTTP_BAD_REQUEST);
        }
        if (is_null($checkpoint) || false == trim($checkpoint)) { // PHP evaluates an empty string to false
            return new Response('Invalid checkpoint!', Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        /** @var RegistrationRepository $repo */
        $repo = $em->getRepository('AppBundle:Registration');
        /** @var Registration $registration */
        $registration = $repo->findOneBy(array('token' => $token));
        if (is_null($registration)) {
            return new Response('Invalid token!', Response::HTTP_NOT_FOUND);
        }

        try {
            $repo->setTime($registration, $time, $checkpoint, $this->get('logger'));
        } catch (\InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_FORBIDDEN);
        }

        return new Response('', Response::HTTP_OK);
    }
}