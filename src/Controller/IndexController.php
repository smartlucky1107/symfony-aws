<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        return $this->redirect('https://swapcoin.today/');
//        return $this->render('index/index.html.twig', [
//            'controller_name' => 'IndexController',
//        ]);
    }
}
