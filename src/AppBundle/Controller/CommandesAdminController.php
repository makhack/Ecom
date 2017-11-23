<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\UtilisateursAdresses;
use AppBundle\Entity\Commandes;
use AppBundle\Entity\Produits;

class CommandesAdminController extends Controller
{
    public function commandesAction() 
    {
        $em = $this->getDoctrine()->getManager();
        $commandes = $em->getRepository('EcommerceBundle:Commandes')->findAll();
        
        return $this->render('EcommerceBundle:Administration:Commandes/layout/index.html.twig', array('commandes' => $commandes));
    }
    
    public function showFactureAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $facture = $em->getRepository('EcommerceBundle:Commandes')->find($id);
        
        if (!$facture) {
            $this->get('session')->getFlashBag()->add('error', 'Une erreur est survenue');
            return $this->redirect($this->generateUrl('adminCommande'));
        }
        
        $this->container->get('setNewFacture')->facture($facture);
    }
}
