<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/products/{limit}/{offset}/{order}", name="products")
     */
    public function productsAction($limit=10, $offset=0, $order='asc', Request $request)
    {
        $client = $this->get('csa_guzzle.client.bilemo');
        $api = 'http://127.0.0.1:8000/api/products?limit='.$limit.
                '&offset='.$offset.
                '&order='.$order
        ;

        $response = $client->get($api, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->get('session')->get('access_token'),
            ]
        ]);

        $products = json_decode($response->getBody()->getContents(), true);
        // for pagination
        $totalPages = ceil($products['meta']['total_items']/$products['meta']['limit']);
        $actualPage = ceil($products['meta']['offset']/$products['meta']['limit']);

        return $this->render('default/products.html.twig', array(
            'products'      =>  $products,
            'totalPages'    =>  $totalPages,
            'actualPage'    =>  $actualPage,
            'limit'         =>  $products['meta']['limit']
        ));
    }

    /**
     * @Route("/product/{id}", name="get_product", requirements={"id"="\d+"})
     */
    public function getProductAction($id)
    {
        $client = $this->get('csa_guzzle.client.bilemo');
        $api = 'http://127.0.0.1:8000/api/products/'.$id;

        try {
            $response = $client->get($api, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->get('session')->get('access_token'),
                ]
            ]); 
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $r = $e->getResponse();
            $error = json_decode($r->getBody()->getContents(), true);
            if ($error['code'] === 404) {
                throw $this->createNotFoundException('The product does not exist.');
            }
        }
        $product = json_decode($response->getBody()->getContents(), true);
        return $this->render('product/product.html.twig', array(
            'product'      =>  $product,
        ));
    }
}
