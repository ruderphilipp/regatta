<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use AppBundle\Entity\Event;
use AppBundle\Entity\RacingGroupMembership;

use AppBundle\DRV_Import\Athlete;
use AppBundle\DRV_Import\Boat;
use AppBundle\DRV_Import\Club;
use AppBundle\DRV_Import\Race;
use AppBundle\DRV_Import\Representative;
/**
 * Import controller for DRV data.
 */
class DrvImportController extends Controller
{
    const DRV_DEBUG = true;

    /**
     * Upload file and import it into database
     *
     * @Route("/event/{id}/import", name="drv_import_index")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request, Event $event)
    {
        $data = array();

        $form = $this->createFormBuilder($data)
            ->add('xmlfile', FileType::class, array('label' => 'XML-Datei'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $data2 */
            $data2 = $form->getData()['xmlfile'];
            $err = false;
            if ($data2->getClientSize() != $data2->getSize()) {
                $this->addFlash(
                    'error',
                    'Unterschiedliche Dateigröße! Bitte nochmals hochladen.'
                );
                $err = true;
            }
            if ($data2->getClientMimeType() != 'text/xml' || strtolower($data2->getClientOriginalExtension()) != 'xml') {
                $this->addFlash(
                    'error',
                    'Nur XML-Export-Dateien sind erlaubt!'
                );
                $err = true;
            }

            if (!$err) {
                $err = $this->is_valid_drv_file($data2->getPathname());
            }
            if (!$err) {
                $this->importData($data2->getPathname(), $event);

                $this->addFlash(
                    'notice',
                    'Daten erfolgreich importiert!'
                );

                return $this->redirectToRoute('race_index', array('id' => $event->getId()));
            }

        }

        return $this->render('drv_import/index.html.twig', array(
            'form' => $form->createView(),
            'event' => $event,
        ));
    }

    /**
     * Check a given XML file against the DRV rules
     *
     * @param string $pathToFile full path to the XML file
     * @return bool if there were any errors during processing
     */
    private function is_valid_drv_file($pathToFile) {
        $hasErrors = false;
        // Enable user error handling
        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->load($pathToFile);

        $pathToSchema = realpath($this->get('kernel')->getRootDir() . '/Resources/drv_import/meldungen_2010.xsd');

        if (!file_exists($pathToSchema)) {
            $message = 'Konnte DRV-Schema auf Server nicht finden!';
            $this->addFlash(
                'error',
                $message
            );
            $this->get('logger')->warning($message . ' Gesuchter Pfad: ' . $pathToSchema);
            $hasErrors = true;
        }

        if (!$hasErrors && !$xml->schemaValidate($pathToSchema)) {
            if (self::DRV_DEBUG) {
                print '<b>DOMDocument::schemaValidate() generated Errors!</b>' . "\n";
                $errors = libxml_get_errors();
                libxml_clear_errors();
                foreach ($errors as $error) {
                    print '<<<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
                    print $this->libxml_display_error($error);
                    print_r($error);
                    print '>>>>>>>>>>>>>>>>>>>>>>>>>' . "\n";
                }
            } else {
                $this->addFlash(
                    'error',
                    'Nur XML-Export-Dateien vom DRV sind erlaubt!'
                );
                $hasErrors = true;
            }
        }
        return $hasErrors;
    }

    private function libxml_display_error($error)
    {
        $return = "<br/>\n";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "<b>Warning {$error->code}</b>: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "<b>Error {$error->code}</b>: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "<b>Fatal Error {$error->code}</b>: ";
                break;
        }
        $return .= trim($error->message);
        if ($error->file) {
            $return .=    " in <b>$error->file</b>";
        }
        $return .= " on line <b>$error->line</b>\n";

        return $return;
    }

    /**
     * @param $filename string file from where to import the data
     */
    private function importData($filename, Event $event)
    {
        $xml = simplexml_load_file($filename);

        // see <http://stackoverflow.com/questions/4411340/>
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $xml["stand"]);

        $clubs = array();
        $races = array();
        $boats = array();
        $athletes = array();

        foreach ($xml->vereine->verein as $club) {
            $x = new Club($club);
            $clubs[$x->drv_id] = $x;
        }

        foreach ($xml->meldungen->rennen as $r) {
            $race = new Race($r);
            $races[$race->number] = $race;

            foreach ($r->meldung as $registration) {
                $boat = new Boat($registration);
                $boats[$boat->id] = $boat;

                foreach($registration->mannschaft->position as $crewmember) {
                    $position = (int)(string)$crewmember['nr'];
                    $is_cox = ('true' == (string)$crewmember['st']);

                    foreach($crewmember->athlet as $athlete) {
                        $a = new Athlete($athlete);
                        $athletes[$a->drv_id] = $a;
                        $boat->add($a, $position, $is_cox);
                    }
                }
                $race->add($boat);
            }
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ClubRepository $clubRepo */
        $clubRepo = $em->getRepository('AppBundle:Club');
        foreach ($clubs as $club) {
            $clubRepo->createOrUpdate($club, $this->get('logger'));
        }
        // save into DB
        $em->flush();

        /** @var \AppBundle\Repository\CompetitorRepository $athleteRepo */
        $athleteRepo = $em->getRepository('AppBundle:Competitor');
        /** @var \AppBundle\Repository\MembershipRepository $membershipRepo */
        $membershipRepo = $em->getRepository('AppBundle:Membership');
        // cache
        $membershipPerAthlete = array("_" => "");
        foreach ($athletes as $athlete) {
            try {
                $a = $athleteRepo->createOrUpdate($athlete, $this->get('logger'));
                $m = $membershipRepo->createOrUpdate($a, $athlete->club_id, $date, $this->get('logger'));
                $membershipPerAthlete[$athlete->drv_id] = $m;
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $e->getMessage()
                );
            }
        }
        // save into DB
        $em->flush();

        /** @var \AppBundle\Repository\RaceRepository $raceRepo */
        $raceRepo = $em->getRepository('AppBundle:Race');
        /** @var \AppBundle\Repository\RacingGroupRepository $groupRepo */
        $groupRepo = $em->getRepository('AppBundle:RacingGroup');
        /** @var \AppBundle\DRV_Import\Race $race */
        foreach($races as $race) {
            $r = $raceRepo->createOrUpdate($race, $event, $this->get('logger'));

            if (null != $r) {
                /** @var \AppBundle\DRV_Import\Boat $boat */
                foreach($race->getBoats() as $boat) {
                    $b = $groupRepo->createOrUpdate($boat, $r, $this->get('logger'));
                    // map athletes->boat
                    $positions = $boat->getPositions();
                    foreach (array_keys($positions) as $i) {
                        $a_id = $positions[$i]['athlete'];
                        if (null == $a_id) {
                            $message = "No athlete at position {$i} in group {$b->getName()}";
                            $this->get('logger')->warning($message);
                            echo $message . "\n";
                        } else {
                            if (!array_key_exists($a_id, $membershipPerAthlete)) {
                                $message = "No membership for athlete {$a_id}";
                                $this->get('logger')->warning($message);
                                echo $message . "\n";
                            } else {
                                $m = $membershipPerAthlete[$a_id];
                                // FIXME use createOrUpdate to avoid duplicates
                                $rgm = new RacingGroupMembership();
                                $rgm->setGroup($b)
                                    ->setPosition($i)
                                    ->setIsCox($positions[$i]['is_cox'])
                                    ->setMembership($m);
                                $em->persist($rgm);
                            }
                        }
                    }
                }
                $em->flush();
            } else {
                $this->addFlash(
                    'error',
                    "Could not find race for [{$race->specification}, {$race->number}]"
                );
            }
        }
    }
}
