<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalController extends AbstractController
{
    #[Route('/politique-confidentialite', name: 'app_politique_confidentialite')]
    public function index(): Response
    {
        return $this->render('legal/politique_confidentialite.html.twig');
    }

     #[Route('/legal/mentions-legales', name: 'legal_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig');
    }

    #[Route('/legal/gestion-cookies', name: 'legal_gestion_cookies')]
    public function gestionCookies(): Response
    {
        return $this->render('legal/gestion_cookies.html.twig');
    }

    #[Route('/legal/conditions-utilisation', name: 'legal_conditions_utilisation')]
    public function conditionsUtilisation(): Response
    {
        return $this->render('legal/conditions_utilisation.html.twig');
    }

}
