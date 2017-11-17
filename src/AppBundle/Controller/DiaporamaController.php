<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Diaporama;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Diaporama controller.
 *
 * @Route("diaporama")
 */
class DiaporamaController extends Controller
{
    /**
     * Lists all diaporama entities.
     *
     * @Route("/", name="diaporama_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $diaporamas = $em->getRepository('AppBundle:Diaporama')->findAll();

        return $this->render('diaporama/index.html.twig', array(
            'diaporamas' => $diaporamas,
        ));
    }

    /**
     * Creates a new diaporama entity.
     *
     * @Route("/new", name="diaporama_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $diaporama = new Diaporama();
        $form = $this->createForm('AppBundle\Form\DiaporamaType', $diaporama);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($diaporama);
            $em->flush();

            return $this->redirectToRoute('diaporama_show', array('id' => $diaporama->getId()));
        }

        return $this->render('diaporama/new.html.twig', array(
            'diaporama' => $diaporama,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a diaporama entity.
     *
     * @Route("/{id}", name="diaporama_show")
     * @Method("GET")
     */
    public function showAction(Diaporama $diaporama)
    {
        $deleteForm = $this->createDeleteForm($diaporama);

        return $this->render('diaporama/show.html.twig', array(
            'diaporama' => $diaporama,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing diaporama entity.
     *
     * @Route("/{id}/edit", name="diaporama_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Diaporama $diaporama)
    {
        $deleteForm = $this->createDeleteForm($diaporama);
        $editForm = $this->createForm('AppBundle\Form\DiaporamaType', $diaporama);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('diaporama_edit', array('id' => $diaporama->getId()));
        }

        return $this->render('diaporama/edit.html.twig', array(
            'diaporama' => $diaporama,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a diaporama entity.
     *
     * @Route("/{id}", name="diaporama_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Diaporama $diaporama)
    {
        $form = $this->createDeleteForm($diaporama);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($diaporama);
            $em->flush();
        }

        return $this->redirectToRoute('diaporama_index');
    }

    /**
     * Creates a form to delete a diaporama entity.
     *
     * @param Diaporama $diaporama The diaporama entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Diaporama $diaporama)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('diaporama_delete', array('id' => $diaporama->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
