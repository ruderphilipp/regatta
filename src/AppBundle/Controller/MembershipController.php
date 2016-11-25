<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Competitor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Membership;

/**
 * Membership controller.
 *
 * @Route("/membership")
 */
class MembershipController extends Controller
{
    /**
     * Creates a new Membership entity.
     *
     * @Route("/new/{competitor}", name="membership_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function newAction(Request $request, Competitor $competitor)
    {
        $membership = new Membership();
        $em = $this->getDoctrine()->getManager();

        // find all clubs where the given competitor is _not_ a member
        $myClubs = $competitor->getMemberships()->map(function($membership) {
            return $membership->getClub()->getId();
        });
        $clubQb = $em->getRepository('AppBundle:Club')->createQueryBuilder('c');
        if (0 < count($myClubs)) {
            $clubQb = $clubQb->where('c.id NOT IN (:clubs)')->setParameter('clubs', $myClubs);
        }
        $clubQ = $clubQb->getQuery();
        $clubs = $clubQ->getResult();

        $form = $this->createForm('AppBundle\Form\MembershipType', $membership, array(
            'competitors' => array($competitor),
            'clubs' => $clubs));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($membership);
            $em->flush();

            $this->addFlash(
                'notice',
                'Neue Mitgliedschaft wurde angelegt!'
            );

            return $this->redirectToRoute('competitor_show', array('id' => $competitor->getId()));
        }

        return $this->render('membership/new.html.twig', array(
            'membership' => $membership,
            'person' => $competitor,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Membership entity.
     *
     * @Route("/{id}/edit", name="membership_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function editAction(Request $request, Membership $membership)
    {
        $deleteForm = $this->createDeleteForm($membership);
        $em = $this->getDoctrine()->getManager();
        $clubRepo = $em->getRepository('AppBundle:Club');

        // find all clubs where the given competitor is _not_ a member plus the current one
        $myClubs = $membership->getPerson()->getMemberships()
            ->filter(function($m) use (&$membership) {
                return $m->getId() != $membership->getId();
            })
            ->map(function($m) {
                return $m->getClub()->getId();
            });
        if (0 == count($myClubs)) {
            $clubs = $clubRepo->findAll();
        } else {
            $clubQb = $clubRepo->createQueryBuilder('c');
            $clubQ = $clubQb->where('c.id NOT IN (:clubs)')->setParameter('clubs', $myClubs)->getQuery();
            $clubs = $clubQ->getResult();
        }

        $editForm = $this->createForm('AppBundle\Form\MembershipType', $membership, array(
            'competitors' => array($membership->getPerson()),
            'clubs' => $clubs));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membership);
            $em->flush();

            $this->addFlash(
                'notice',
                'Mitgliedschaft geändert!'
            );

            return $this->redirectToRoute('competitor_show', array('id' => $membership->getPerson()->getId()));
        }

        return $this->render('membership/edit.html.twig', array(
            'membership' => $membership,
            'person' => $membership->getPerson(),
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Membership entity.
     *
     * @Route("/{id}", name="membership_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function deleteAction(Request $request, Membership $membership)
    {
        $form = $this->createDeleteForm($membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($membership);
            $em->flush();

            $this->addFlash(
                'notice',
                'Mitgliedschaft gelöscht!'
            );
        }

        return $this->redirectToRoute('competitor_show', array('id' => $membership->getPerson()->getId()));
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
