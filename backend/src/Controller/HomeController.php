<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    // Le site Symfony ne sert que le back-office : l'accueil renvoie vers EasyAdmin (login si besoin)
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin');
    }
}
