<?php

namespace AppBundle\Controller;

use AppBundle\Entity\C2ErgoInfo;
use AppBundle\Entity\C2SkipLane;
use AppBundle\Entity\Competitor;
use AppBundle\Entity\Event;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * Import a result file after successfully doing a race outside with VRA.
     *
     * @Route("/event/{event}/concept2/import/", name="concept2_import")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REFEREE')")
     * @return RedirectResponse|Response
     */
    public function importAction(Request $request, Event $event)
    {
        $data = array();

        $form = $this->createFormBuilder($data)
            ->add('import_file', FileType::class, array('label' => 'Ergebnis-Datei (*.rac_result.txt)'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $data2 */
            $data2 = $form->getData()['import_file'];
            $err = false;
            if ($data2->getClientSize() != $data2->getSize()) {
                $this->addFlash(
                    'error',
                    'Unterschiedliche Dateigröße! Bitte nochmals hochladen.'
                );
                $err = true;
            }
            if ($data2->getClientMimeType() != 'text/plain' || strtolower($data2->getClientOriginalExtension()) != 'txt') {
                $this->addFlash(
                    'error',
                    'Nur TXT-Export-Dateien sind erlaubt!'
                );
                $err = true;
            }

            if (!$err) {
                $err = $this->is_valid_import_file($data2->getPathname());
            }
            if (!$err) {
                $ok = $this->importData($data2->getPathname(), $event);

                if ($ok) {
                    $this->addFlash(
                        'notice',
                        'Daten erfolgreich importiert!'
                    );
                    return $this->redirectToRoute('race_index', array('event' => $event->getId()));
                }
            }

        }

        return $this->render(':concept2:import.html.twig', array(
            'form' => $form->createView(),
            'event' => $event,
        ));
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
            $myRaceName = $this->getRaceName($section);
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
            $raceNameParts[] = $this->getRaceName($section);
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
     * Create a standardized race name for each given section.
     *
     * @param RaceSection $section The section to start.
     * @return string Race name.
     */
    private function getRaceName(RaceSection $section)
    {
        return sprintf('R%03d-%02d', $section->getRace()->getNumberInEvent(), $section->getNumber());
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

    private function is_valid_import_file($filename)
    {
        $hasErrors = false;
        // check if JSON decoding is possible without errors
        // see https://secure.php.net/manual/en/function.json-decode.php#110820
        $content = file_get_contents($filename);
        $content = utf8_encode($content);
        $data = json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Keine valide JSON-Datei!';
            $this->addFlash(
                'error',
                $message . ' - ' . json_last_error_msg()
            );
            $this->get('logger')->warning($message . ' Gesuchter Pfad: ' . $filename);
            $hasErrors = true;
        }
        return $hasErrors;
    }

    private function importData($filename, Event $event)
    {
        // read file content and parse json
        $content = file_get_contents($filename);
        $content = utf8_encode($content);
        $data = json_decode($content, true);

        $em = $this->getDoctrine()->getManager();
        // get start time
        $startTime = $data['start'];
        if (!is_numeric($startTime) || 0 >= $startTime) {
            $this->addFlash(
                'error',
                'Importdatei hat keine Startzeit!'
            );
            return false;
        }

        $anyImport = false;
        foreach ($data['classes'] as $raceName => $competitors) {
            $section = $this->getSectionForRaceName($raceName);
            if (!$this->isOkForImport($section, $raceName, $competitors)) {
                continue;
            }

            $timingController = $this->get('app.timing_controller');
            if (!$section->isStarted()) {
                // start the race section (and all "competitors" (registrations)) with the given start time
                $timingController->markAsStarted($section, $section->getId(), $em, $startTime);
            }

            // store splits as checkpoints
            $registrationRepo = $em->getRepository('AppBundle:Registration');
            foreach ($competitors as $competitor) {
                $registration = $registrationRepo->find($competitor['id']);
                $token = $registration->getTeam()->getToken();
                foreach ($competitor['splits'] as $split) {
                    $meters = $split['meters'];
                    $checkpoint = "{$meters}m";
                    $time = $split['total_time'];
                    $r = new Request(array(
                        'token' => $token,
                        'checkpoint' => $checkpoint,
                        'time' => $startTime + $time,
                    ));
                    $this->get('logger')->info("Concept2Controller::importData: {$r}");
                    $response = $timingController->setCheckpointTimeAction($r);
                    if (Response::HTTP_OK != $response->getStatusCode()) {
                        $this->get('logger')->warning("Concept2Controller::importData: {$response->getContent()} - {$r}");
                    } else {
                        $anyImport = true;
                    }
                }
                // finishing
                $time = $competitor['final_time_sec'];
                $r = new Request(array(
                    'token' => $token,
                    'checkpoint' => Registration::CHECKPOINT_FINISH,
                    'time' => $startTime + $time,
                ));
                $response = $timingController->setCheckpointTimeAction($r);
                if (Response::HTTP_OK != $response->getStatusCode()) {
                    $this->get('logger')->warning("Concept2Controller::importData: {$response->getContent()} - FINISH - {$r}");
                } else {
                    $anyImport = true;
                }

            }
        }
        if ($anyImport) {
            // flush entity manager
            $em->flush();
        }
        return $anyImport;
    }

    private function getSectionForRaceName($name)
    {
        if (strlen($name) != 7 || 1 !== preg_match('/R\d{3}-\d{2}/', $name)) {
            $message = "Kein valider Rennname! Import nicht möglich für \"{$name}\"!";
            $this->addFlash(
                'error',
                $message
            );
            $this->get('logger')->warning("Concept2Controller::getSectionForRaceName: {$message}");
            return null;
        }

        $raceId = intval(substr($name, 1, 3));
        $number = intval(substr($name, 5,2));

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:RaceSection');
        $section = $repo->findOneBy(array('race' => $raceId, 'number' => $number));

        return $section;
    }

    /**
     * @param RaceSection|null $section
     * @param $raceName
     * @param array $competitors
     * @return bool
     */
    private function isOkForImport($section, $raceName, array $competitors)
    {
        // section not importable/ errors
        if (null == $section) {
            return false;
        }
        // check that it is not finished yet (duplicated imports)
        if ($section->isFinished()) {
            $this->addFlash(
                'notice',
                "Abteilung {$this->getRaceName($section)} übersprungen, da bereits fertig."
            );
            return false;
        }
        // check that the registrations in the race section and the "competitors" IDs match
        $competitorIDs = array();
        foreach ($competitors as $c) {
            $competitorIDs[] = $c['id'];
        }
        $invalidIds = $this->getAllIdsThatAreNotPartOfThisSection($section, $competitorIDs);
        if (!empty($invalidIds)) {
            foreach ($competitors as $c) {
                if (in_array($c['id'], $invalidIds)) {
                    $message =  "{$c['name']} ist kein Starter in {$raceName}!";
                    $this->addFlash('error', $message);
                }
            }
            // skip import
            return false;
        }
        // check that the distance is less or equal to the race distance
        $raceDistance = $section->getRace()->getDistance();
        if (!is_null($raceDistance)) {
            $has_errors = false;
            foreach ($competitors as $c) {
                if ($c['meters_rowed'] > $raceDistance) {
                    $message = "Distanz zu groß ({$c['meters_rowed']} > {$raceDistance}) bei Rennen {$raceName} für \"{$c['name']}\"";
                    $this->addFlash('error', $message);
                    $has_errors = true;
                }
            }
            if ($has_errors) {
                return false;
            }
        }
        // whoohoo! Everything works fine!
        return true;
    }

    /**
     * Check that the registrations in the race section and the given "competitors" IDs match.
     *
     * @param RaceSection $section The section the IDs should be searched at.
     * @param array $ids The IDs to look for.
     * @return array All IDs that do not match.
     */
    private function getAllIdsThatAreNotPartOfThisSection(RaceSection $section, array $ids)
    {
        $result = array();
        $starters = $section->getValidRegistrations();
        foreach ($ids as $id) {
            $found = false;
            /** @var Registration $starter */
            foreach ($starters as $starter) {
                if ($starter->getId() == $id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = $id;
            }
        }
        return $result;
    }
}
