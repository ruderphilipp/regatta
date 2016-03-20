<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Competitor;
use AppBundle\Form\CompetitorType;

/**
 * Competitor controller.
 *
 * @Route("/competitor")
 */
class CompetitorController extends Controller
{
    /**
     * Lists all Competitor entities.
     *
     * @Route("/", name="competitor_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $competitors = $em->getRepository('AppBundle:Competitor')->findAll();

        return $this->render('competitor/index.html.twig', array(
            'competitors' => $competitors,
        ));
    }

    /**
     * Creates a new Competitor entity.
     *
     * @Route("/new", name="competitor_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $competitor = new Competitor();
        $form = $this->createForm('AppBundle\Form\CompetitorType', $competitor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($competitor);
            $em->flush();

            return $this->redirectToRoute('competitor_show', array('id' => $competitor->getId()));
        }

        return $this->render('competitor/new.html.twig', array(
            'competitor' => $competitor,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Competitor entity.
     *
     * @Route("/{id}", name="competitor_show")
     * @Method("GET")
     */
    public function showAction(Competitor $competitor)
    {
        $deleteForm = $this->createDeleteForm($competitor);

        return $this->render('competitor/show.html.twig', array(
            'competitor' => $competitor,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Competitor entity.
     *
     * @Route("/{id}/edit", name="competitor_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Competitor $competitor)
    {
        $deleteForm = $this->createDeleteForm($competitor);
        $editForm = $this->createForm('AppBundle\Form\CompetitorType', $competitor);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($competitor);
            $em->flush();

            return $this->redirectToRoute('competitor_index');
        }

        return $this->render('competitor/edit.html.twig', array(
            'competitor' => $competitor,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Competitor entity.
     *
     * @Route("/{id}", name="competitor_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Competitor $competitor)
    {
        $form = $this->createDeleteForm($competitor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($competitor);
            $em->flush();
        }

        return $this->redirectToRoute('competitor_index');
    }

    /**
     * Creates a form to delete a Competitor entity.
     *
     * @param Competitor $competitor The Competitor entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Competitor $competitor)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('competitor_delete', array('id' => $competitor->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
