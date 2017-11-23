<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\UtilisateursAdresses;
use AppBundle\Entity\Commandes;
use AppBundle\Entity\Produits;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CommandesController extends Controller
{
    public function facture($request)
    {
        $em = $this->getDoctrine()->getManager();
        dump('generator');
        $random = random_int(1, 20);
        //$generator = $this->container->get('security.secure_random');

        $session = $request->getSession();
        $adresse = $session->get('adresse');
        $panier = $session->get('panier');
        $commande = array();
        $totalHT = 0;
        $totalTVA = 0;
        
        $facturation = $em->getRepository('AppBundle:UtilisateursAdresses')->find($adresse['facturation']);
        $livraison = $em->getRepository('AppBundle:UtilisateursAdresses')->find($adresse['livraison']);
        $produits = $em->getRepository('AppBundle:Product')->findArray(array_keys($session->get('panier')));
        
        foreach($produits as $produit)
        {
            $prixHT = ($produit->getPrice() * $panier[$produit->getId()]);
            $prixTTC = ($produit->getPrice() * $panier[$produit->getId()] / $produit->getTva()->getMultiplicate());
            $totalHT += $prixHT;
            
            if (!isset($commande['tva']['%'.$produit->getTva()->getValeur()]))
                $commande['tva']['%'.$produit->getTva()->getValeur()] = round($prixTTC - $prixHT,2);
            else
                $commande['tva']['%'.$produit->getTva()->getValeur()] += round($prixTTC - $prixHT,2);
            
            $totalTVA += round($prixTTC - $prixHT,2);
            
            $commande['produit'][$produit->getId()] = array('reference' => $produit->getName(),
                                                            'quantite' => $panier[$produit->getId()],
                                                            'prixHT' => round($produit->getPrice(),2),
                                                            'prixTTC' => round($produit->getPrice() / $produit->getTva()->getMultiplicate(),2));
        }
        
        $commande['livraison'] = array('prenom' => $livraison->getPrenom(),
                                    'nom' => $livraison->getNom(),
                                    'telephone' => $livraison->getTelephone(),
                                    'adresse' => $livraison->getAdresse(),
                                    'cp' => $livraison->getCp(),
                                    'ville' => $livraison->getVille(),
                                    'pays' => $livraison->getPays(),
                                    'complement' => $livraison->getComplement());

        $commande['facturation'] = array('prenom' => $facturation->getPrenom(),
                                    'nom' => $facturation->getNom(),
                                    'telephone' => $facturation->getTelephone(),
                                    'adresse' => $facturation->getAdresse(),
                                    'cp' => $facturation->getCp(),
                                    'ville' => $facturation->getVille(),
                                    'pays' => $facturation->getPays(),
                                    'complement' => $facturation->getComplement());

        $commande['prixHT'] = round($totalHT,2);
        $commande['prixTTC'] = round($totalHT + $totalTVA,2);
        $commande['token'] = bin2hex($random);

        return $commande;
    }
    
    public function prepareCommandeAction(Request $request)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        
        if (!$session->has('commande'))
            $commande = new Commandes();
        else
            $commande = $em->getRepository('AppBundle:Commandes')->find($session->get('commande'));
        
        $commande->setDate(new \DateTime());
        $commande->setUtilisateur($this->getUser());
        $commande->setValider(0);
        $commande->setReference(0);
        $commande->setCommande($this->facture($request));
        
        if (!$session->has('commande')) {
            $em->persist($commande);
            $session->set('commande',$commande);
        }
        
        $em->flush();
        
        return new Response($commande->getId());
    }
    

    /**
     * Add a new product entity into cart.
     *
     * @Route("/api/banque/{id}", name="validationCommande")
     * @Method({"POST"})
     */
    public function validationCommandeAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $commande = $em->getRepository('AppBundle:Commandes')->find($id);
        
        if (!$commande || $commande->getValider() == 1)
            throw $this->createNotFoundException('La commande n\'existe pas');
        
        $commande->setValider(1);
        $commande->setReference($this->container->get('setNewReference')->reference()); //Service
        $em->flush();   
        
        $session = $this->getRequest()->getSession();
        $session->remove('adresse');
        $session->remove('panier');
        $session->remove('commande');
        
        $this->get('session')->getFlashBag()->add('success','Votre commande est validé avec succès');
        return $this->redirect($this->generateUrl('factures'));
    }
}
