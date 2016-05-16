<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use AppBundle\Entity\Event;

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
//                echo 'checking file...' . "<br>\n";
                $err = $this->is_valid_drv_file($data2->getPathname());
            }
            if (!$err) {
//                echo 'reading file...' . "<br>\n";

                $xml = simplexml_load_file($data2->getPathname());

                $representatives = array();
                $clubs = array();
                $races = array();
                $boats = array();
                $athletes = array();

                echo 'parsing representatives...'."<br>\n";
                foreach ($xml->obleute->obmann as $rep) {
                    $x = new Representative($rep);
                    $representatives[$x->id] = $x;
                }

                echo 'parsing clubs...'."<br>\n";
                foreach ($xml->vereine->verein as $club) {
                    $x = new Club($club);
                    $clubs[$x->drv_id] = $x;
                }

                echo 'parsing races...'."<br>\n";
                echo '<pre>';
                foreach ($xml->meldungen->rennen as $r) {
                    $race = new Race($r);
                    $races[$race->number] = $race;

//                    echo '--------------------------------------'."\n";
//                    echo '['.$race->number.'] ';
//                    echo $race->specification."\n";

                    foreach ($r->meldung as $registration) {
                        $boat = new Boat($registration);
                        $boats[$boat->id] = $boat;

//                        echo "\t".$boat->id." - ".$boat->name."\n";

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

//                echo '===== representatives =========================='."\n";
//                var_dump($representatives);
                echo '===== clubs ===================================='."\n";
                var_dump($clubs);
//                echo '===== races ===================================='."\n";
//                var_dump($races);
//                echo '===== boats ===================================='."\n";
//                var_dump($boats);
//                echo '===== athletes ================================='."\n";
//                var_dump($athletes);
                echo '</pre>';

                $this->addFlash(
                    'notice',
                    'Daten erfolgreich importiert!'
                );

                // return $this->redirectToRoute('race_index', array('id' => $race->getEvent()->getId()));
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
            $this->addFlash(
                'error',
                'Konnte DRV-Schema auf Server nicht finden!'
            );
            $hasErrors = true;
        }

        if (!$xml->schemaValidate($pathToSchema)) {
            if (self::DRV_DEBUG) {
                print '<b>DOMDocument::schemaValidate() generated Errors!</b>' . "\n";
                $errors = libxml_get_errors();
                libxml_clear_errors();
                foreach ($errors as $error) {
                    print '<<<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
                    print libxml_display_error($error);
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
}