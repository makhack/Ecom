<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Form\UtilisateursAdressesType;
use AppBundle\Entity\UtilisateursAdresses;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;

/**
 * Product controller.
 *
 * @Route("/panier")
 */

class PanierController extends Controller
{
	     /**
     * Add a new product entity into cart.
     *
     * @Route("/menu", name="menu")
     * @Method({"GET"})
     */
    public function menuAction( Request $request)
    {
        $session = $request->getSession();
        if (!$session->has('panier'))
            $articles = 0;
        else
            $articles = count($session->get('panier'));
        
        return $this->render('AppBundle:Default:panier/modulesUsed/panier.html.twig', array('articles' => $articles));
    }

    
    /**
     * Deletes a product entity into cart.
     *
     * @Route("delete/{id}", name="cart_product_delete")
     * @Method("DELETE")
     */
    
    public function supprimerAction($id)
    {
        $session = $this->getRequest()->getSession();
        $panier = $session->get('panier');
        
        if (array_key_exists($id, $panier))
        {
            unset($panier[$id]);
            $session->set('panier',$panier);
            $this->get('session')->getFlashBag()->add('success','Article supprimé avec succès');
        }
        
        return $this->redirect($this->generateUrl('panier')); 
    }
    
     /**
     * Add a new product entity into cart.
     *
     * @Route("/ajouter/{id}", name="cart_product_add")
     * @Method({"GET"})
     */

    public function ajouterAction($id, Request $request)
    {
        $session = $request->getSession();
        
        if (!$session->has('panier')) $session->set('panier',array());
        $panier = $session->get('panier');
        
        if (array_key_exists($id, $panier)) {
            if ($request->query->get('qte') != null) $panier[$id] = $request->query->get('qte');
            $this->get('session')->getFlashBag()->add('success','Quantité modifié avec succès');
        } else {
            if ($request->query->get('qte') != null)
                $panier[$id] = $request->query->get('qte');
            else
                $panier[$id] = 1;
            
            $this->get('session')->getFlashBag()->add('success','Article ajouté avec succès');
        }
            
        $session->set('panier',$panier);
        
        
        return $this->redirect($this->generateUrl('panier'));
    }

     /**
     * Add a new product entity into cart.
     *
     * @Route("/", name="panier")
     * @Method({"GET"})
     */
    public function panierAction(Request $request)
    {
        $session = $request->getSession();
        if (!$session->has('panier')) $session->set('panier', array());
        
        $em = $this->getDoctrine()->getManager();
        $produits = $em->getRepository('AppBundle:Product')->findArray(array_keys($session->get('panier')));
        
        return $this->render('AppBundle:Default:panier/layout/panier.html.twig', array('produits' => $produits,
                                                                                             'panier' => $session->get('panier')));
    }
    
 	/**
     * remove a product entity into cart.
     *
     * @Route("/livraison/adresse/suppression/{id}", name="livraisonAdresseSuppression")
     * @Method({"POST"})
     */
    public function adresseSuppressionAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:UtilisateursAdresses')->find($id);
        
        if ($this->container->get('security.context')->getToken()->getUser() != $entity->getUtilisateur() || !$entity)
            return $this->redirect ($this->generateUrl ('livraison'));
        
        $em->remove($entity);
        $em->flush();
        
        return $this->redirect ($this->generateUrl ('livraison'));
    }
    
     /**
     * Add a new product entity into cart.
     *
     * @Route("/livraison", name="livraison")
     * @Method({"GET","POST"})
     */
    public function livraisonAction(Request $request)
    {
        $utilisateur = $this->getUser();
        $entity = new UtilisateursAdresses();
        $form = $this->createForm('AppBundle\Form\UtilisateursAdressesType', $entity);
        
        if ($request->getMethod() == 'POST')
        {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $entity->setUser($utilisateur);
                $em->persist($entity);
                $em->flush();
                
                return $this->redirect($this->generateUrl('livraison'));
            }
        }
        
        return $this->render('AppBundle:Default:panier/layout/livraison.html.twig', array('utilisateur' => $utilisateur,
                                                                                                'form' => $form->createView()));
    }
    
    public function setLivraisonOnSession(Request $request)
    {
        $session = $request->getSession();
        
        if (!$session->has('adresse')) $session->set('adresse',array());
        $adresse = $session->get('adresse');
        
        if ($request->request->get('livraison') != null && $request->request->get('facturation') != null)
        {
            $adresse['livraison'] = $request->request->get('livraison');
            $adresse['facturation'] = $request->request->get('facturation');
        } else {
            return $this->redirect($this->generateUrl('validation'));
        }
        
        $session->set('adresse',$adresse);
        return $this->redirect($this->generateUrl('validation'));
    }
    

 	/**
     * Add a new product entity into cart.
     *
     * @Route("/validation", name="validation")
     * @Method({"GET","POST"})
     */
    public function validationAction(Request $request)
    {
        if ($request->getMethod() == 'POST')
            $this->setLivraisonOnSession($request);
        
        $em = $this->getDoctrine()->getManager();
        $prepareCommande = $this->forward('AppBundle:Commandes:prepareCommande');
        $commande = $em->getRepository('AppBundle:Commandes')->find($prepareCommande->getContent());
        
        return $this->render('AppBundle:Default:panier/layout/validation.html.twig', array('commande' => $commande));
    }
}
