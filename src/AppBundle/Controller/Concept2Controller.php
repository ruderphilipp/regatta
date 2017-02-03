<?php

namespace AppBundle\Controller;

use AppBundle\Entity\C2ErgoInfo;
use AppBundle\Entity\C2SkipLane;
use AppBundle\Entity\Competitor;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Concept 2 VRA import/export controller.
 */
class Concept2Controller extends Controller
{
    /**
     * Creates a new Race export file (for a single section).
     *
     * @Route("/concept2/export/{section}", name="concept2_export_single")
     * @Method("GET")
     * @Security("has_role('ROLE_REFEREE')")
     * @return RedirectResponse|Response
     */
    public function singleExportAction(Request $request, RaceSection $section)
    {
        try {
            $result = $this->getExport(array($section->getId()));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash(
                'warning',
                $e->getMessage()
            );
            $result = $this->redirect($request->headers->get('referer'));
        }
        return $result;
    }

    /**
     * Creates a new Race export file (starting multiple sections in one race).
     *
     * @Route("/concept2/export/", name="concept2_export_multiple")
     * @Method("POST")
     * @Security("has_role('ROLE_REFEREE')")
     * @return RedirectResponse|Response
     */
    public function multiExportAction(Request $request)
    {
        $sectionIDs = $request->get('sections', null);
        try {
            $result = $this->getExport($sectionIDs);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash(
                'warning',
                $e->getMessage()
            );
            $result = $this->redirect($request->headers->get('referer'));
        }
        return $result;
    }

    /**
     * Create one race export file <i>for download</i> with the given section IDs
     *
     * @param array $sectionIDs 1..n section IDs
     * @return Response
     * @throws \InvalidArgumentException if an error occurred
     */
    private function getExport(array $sectionIDs)
    {
        if (is_null($sectionIDs) || 0 == count($sectionIDs)) {
            throw new \InvalidArgumentException('Keine Abteilungen verfügbar - Export nicht möglich!');
        }

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:RaceSection');
        $sections = $repo->findBy(array('id' => $sectionIDs));
        unset($sectionIDs, $repo, $em);

        // sanity checks
        /** @var int $distance */
        $distance = null;
        /** @var RaceSection $section */
        foreach ($sections as $section) {
            $myRaceName = sprintf('R%03d-%02d', $section->getRace()->getNumberInEvent(), $section->getNumber());
            $myDistance = $section->getRace()->getDistance();
            if (is_null($myDistance)) {
                throw new \InvalidArgumentException(
                    sprintf('Keine Distanz für Rennen %s verfügbar - Export nicht möglich!', $myRaceName)
                );
            } elseif (is_null($distance)) {
                $distance = $myDistance;
            } else {
                if ($distance != $myDistance) {
                    throw new \InvalidArgumentException(
                        sprintf('Unterschiedliche Distanz in Rennen %s - Export nicht möglich!', $myRaceName)
                    );
                }
            }
        }
        unset($myDistance, $myRaceName);

        // build race name
        $raceNameParts = array();
        foreach ($sections as $section) {
            $raceNameParts[] = sprintf('R%03d-%02d', $section->getRace()->getNumberInEvent(), $section->getNumber());
        }
        $raceName = implode(' ', $raceNameParts);
        unset($raceNameParts);

        // build ergometer lanes
        $ergometers = array();
        $lastLane = 0;
        foreach ($sections as $section) {
            $lane = 0;
            /** @var Registration $registration */
            foreach ($section->getValidRegistrations() as $registration) {
                $members = $first = $registration->getTeam()->getMembers();
                if ($members->count() == 1) {
                    /** @var Competitor $firstP */
                    $firstP = $members->first()->getMembership()->getPerson();
                    $name = sprintf(
                        '%s %s (%s)',
                        $this->toAscii($firstP->getFirstName()),
                        $this->toAscii($firstP->getLastName()),
                        $this->toAscii($registration->getTeam()->getClub()->getCity())
                    );
                } else {
                    $name = $this->toAscii($registration->getTeam()->getClub()->getShortname());
                    if (0 == strlen(trim($name))) {
                        $name = $this->toAscii($registration->getTeam()->getClub()->getName());
                    }
                }
                $lane = $lastLane + $registration->getLane();
                $ergometers[$lane] = new C2ErgoInfo($registration->getId(), $name, $raceName);
            }
            $lastLane = $lane;
        }
        for ($i = 1; $i <= max(array_keys($ergometers)); $i++) {
            if (!array_key_exists($i, $ergometers)) {
                $ergometers[$i] = new C2SkipLane();
            }
        }

        $response = $this->render('concept2/export.single.rac.twig', array(
            'raceName' => substr($raceName, 0, 16), // string, the Concept software cannot handle more than 16 chars
            'distance' => $distance, // int
            'ergometers' => $ergometers, // array[C2ErgoInfo]
        ));

        $filename = str_replace(' ', '_', $raceName) . '.rac';

        //set headers
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);

        return $response;
    }

    /**
     * Replace all character that are not part of standard ascii
     *
     * @param $in string raw string
     * @return string sanitized string
     */
    private function toAscii($in)
    {
        $result = $in;
        $result = str_replace('ä', 'ae', $result);
        $result = str_replace('ö', 'oe', $result);
        $result = str_replace('ü', 'ue', $result);
        $result = str_replace('ß', 'ss', $result);

        return $result;
    }
}
