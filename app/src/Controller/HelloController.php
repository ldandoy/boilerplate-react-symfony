<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

final class HelloController extends AbstractController
{
    #[Route('/hello', name: 'app_hello')]
    public function index(
        LoggerInterface $logger
    ): Response
    {
        $logger->info('Look, I just used a service!');

        return $this->render('hello/index.html.twig', [
            'controller_name' => 'HelloController',
        ]);
    }

    #[Route('/hello/{prenom}', name: 'app_say_hello')]
    public function hello(string $prenom): Response
    {
        // return new Response("Bonjour $prenom !");
        return $this->render('hello/hello.html.twig', [
            'prenom' => $prenom,
        ]);
    }
}
