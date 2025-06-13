<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class MonPlanningController extends AbstractController
{
    #[Route('/mon-planning', name: 'mon_planning')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté (formateur)
        /** @var \App\Entity\User $formateur */
        $formateur = $this->getUser();

        // Sécurité : si pas connecté, redirige vers login
        if (!$formateur) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer toutes les sessions où ce user est formateur
        $sessions = $em->getRepository(\App\Entity\Session::class)
            ->findBy(['formateur' => $formateur]);

        // Formatage pour FullCalendar.js (événements du planning)
        $calendar_events = [];
        foreach ($sessions as $session) {
            $calendar_events[] = [
                'title' => $session->getNom() . ' (' . $session->getFormation()->getNom() . ')',
                'start' => $session->getDateDebut()->format('Y-m-d'),
                // 'end' => $session->getDateFin() ? $session->getDateFin()->format('Y-m-d') : null, // Ajoute si tu as un champ date de fin
                'backgroundColor' => '#e85c33',
                'borderColor' => '#e85c33',
                // Tu peux ajouter : 'salle' => $session->getSalle()->getNom(), etc.
            ];
        }

        // Passage des infos au twig
        return $this->render('mon_planning/index.html.twig', [
            'formateur' => $formateur,
            'calendar_events' => json_encode($calendar_events),
        ]);
    }
}
