<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Membership;

/**
 * Membership controller.
 *
 * @Route("/membership")
 */
class MembershipController extends Controller
{
    /**
     * Lists all Membership entities.
     *
     * @Route("s/", name="membership_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $memberships = $em->getRepository('AppBundle:Membership')->findAll();

        return $this->render('membership/index.html.twig', array(
            'memberships' => $memberships,
        ));
    }

    /**
     * Creates a new Membership entity.
     *
     * @Route("/new", name="membership_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $membership = new Membership();
        $form = $this->createForm('AppBundle\Form\MembershipType', $membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membership);
            $em->flush();

            return $this->redirectToRoute('membership_show', array('id' => $membership->getId()));
        }

        return $this->render('membership/new.html.twig', array(
            'membership' => $membership,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Membership entity.
     *
     * @Route("/{id}", name="membership_show")
     * @Method("GET")
     */
    public function showAction(Membership $membership)
    {
        $deleteForm = $this->createDeleteForm($membership);

        return $this->render('membership/show.html.twig', array(
            'membership' => $membership,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Membership entity.
     *
     * @Route("/{id}/edit", name="membership_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Membership $membership)
    {
        $deleteForm = $this->createDeleteForm($membership);
        $editForm = $this->createForm('AppBundle\Form\MembershipType', $membership);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membership);
            $em->flush();

            return $this->redirectToRoute('membership_edit', array('id' => $membership->getId()));
        }

        return $this->render('membership/edit.html.twig', array(
            'membership' => $membership,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Membership entity.
     *
     * @Route("/{id}", name="membership_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Membership $membership)
    {
        $form = $this->createDeleteForm($membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($membership);
            $em->flush();
        }

        return $this->redirectToRoute('membership_index');
    }

    /**
     * Creates a form to delete a Membership entity.
     *
     * @param Membership $membership The Membership entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Membership $membership)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('membership_delete', array('id' => $membership->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
