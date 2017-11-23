<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Product controller.
 *
 * @Route("admin/product")
 */
class ProductController extends Controller
{
    /**
     * Lists all product entities.
     *
     * @Route("/", name="product_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository('AppBundle:Product')->findAll();

        return $this->render('product/index.html.twig', array(
            'products' => $products,
        ));
    }

    /**
     * Creates a new product entity.
     *
     * @Route("/new", name="product_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $product = new Product();
        $form = $this->createForm('AppBundle\Form\ProductType', $product);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            if(count($product->getImages()) === 0) {
                $session = new Session();

                // set flash messages
                $session->getFlashBag()->add('notice', 'Il faut mettre au moins une image dans le produit');

                // retrieve messages
                foreach ($session->getFlashBag()->get('notice', array()) as $message) {
                    echo '<div class="flash-notice">'.$message.'</div>';
                }

                return $this->render('product/new.html.twig', array(
                    'product' => $product,
                    'form' => $form->createView(),
                ));
            }

/*            foreach ($product->getImages() as $image) {
                $image->setProduct($product);
            }*/
            
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('product_show', array('id' => $product->getId()));
        }

        return $this->render('product/new.html.twig', array(
            'product' => $product,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a product entity.
     *
     * @Route("/{id}", name="product_show")
     * @Method("GET")
     */
    public function showAction(Product $product)
    {
        $deleteForm = $this->createDeleteForm($product);

        return $this->render('product/show.html.twig', array(
            'product' => $product,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing product entity.
     *
     * @Route("/{id}/edit", name="product_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Product $product)
    {
        $deleteForm = $this->createDeleteForm($product);
        echo '<pre>';
        \Doctrine\Common\Util\Debug::dump($product->getImages());
        echo '</pre>';
        $em = $this->getDoctrine()->getManager();

        $originalImages = new ArrayCollection();

        // Create an ArrayCollection of the current Tag objects in the database
        foreach ($product->getImages() as $image) {
            $originalImages->add($image);
        }
        
        $editForm = $this->createForm('AppBundle\Form\ProductType', $product);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            echo '<pre>';

            \Doctrine\Common\Util\Debug::dump($product->getImages());
            echo '</pre>';
            
            

                    // remove the relationship between the tag and the Task
            foreach ($originalImages as $image) {
                if (false === $product->getImages()->contains($image)) {
                    // remove the Task from the Tag
                    //$image->getProduct()->removeElement($product);

                    // if it was a many-to-one relationship, remove the relationship like this
                    //$image->setProduct(null);

                    //$em->persist($image);

                    // if you wanted to delete the Tag entirely, you can also do that
                    //$em->remove($image);

                    $product->removeImage($image);

                }
            }
            $em->persist($product);

            $em->flush();

            return $this->redirectToRoute('product_edit', array('id' => $product->getId()));
        }

        return $this->render('product/edit.html.twig', array(
            'product' => $product,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a product entity.
     *
     * @Route("/{id}", name="product_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Product $product)
    {
        $form = $this->createDeleteForm($product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * Creates a form to delete a product entity.
     *
     * @param Product $product The product entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Product $product)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('product_delete', array('id' => $product->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
