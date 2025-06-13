<?php

namespace App\Controller\Apprenant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApprenantCalendrierController extends AbstractController
{
    #[Route('/apprenant/calendrier', name: 'apprenant_calendrier')]
    public function index(): Response
    {
        return $this->render('apprenant/apprenant_calendrier.html.twig');
    }
}