<?php

namespace App\Controller\Apprenant;

use App\Entity\User;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApprenantDashboardController extends AbstractController
{
   #[Route('/apprenant/dashboard', name: 'apprenant_dashboard')]
public function dashboard(SessionRepository $sessionRepository): Response
{
    /** @var User $user */
    $user = $this->getUser();
    $groupes = $user->getGroupes();

    $now = new \DateTimeImmutable(); // maintenant
    $todayStart = new \DateTimeImmutable('today midnight');
    $todayEnd = $todayStart->setTime(23, 59, 59);
    $pastWeekStart = $todayStart->modify('-7 days');

    $cours_aujourdhui = [];
    $cours_passes = [];

    // Sessions par groupe
    foreach ($groupes as $groupe) {
        $sessionsDuGroupe = $sessionRepository->findByGroupeAndTimeRange($groupe, $todayStart, $todayEnd);
        foreach ($sessionsDuGroupe as $session) {
            if ($session->getDateFin() > $now) {
                $cours_aujourdhui[] = $session;
            } else {
                $cours_passes[] = $session;
            }
        }

        $sessionsPassees = $sessionRepository->findByGroupeAndTimeRange($groupe, $pastWeekStart, $todayStart->modify('-1 second'));
        $cours_passes = array_merge($cours_passes, $sessionsPassees);
    }

    // Sessions individuelles de l’utilisateur
    $sessionsIndividuelles = $sessionRepository->findByUserAndTimeRange($user, $todayStart, $todayEnd);
    foreach ($sessionsIndividuelles as $session) {
        if ($session->getDateFin() > $now) {
            $cours_aujourdhui[] = $session;
        } else {
            $cours_passes[] = $session;
        }
    }

    $sessionsPasseesIndiv = $sessionRepository->findByUserAndTimeRange($user, $pastWeekStart, $todayStart->modify('-1 second'));
    $cours_passes = array_merge($cours_passes, $sessionsPasseesIndiv);

    return $this->render('apprenant/apprenant_dashboard.html.twig', [
        'user' => $user,
        'cours_aujourdhui' => $cours_aujourdhui,
        'cours_passes' => $cours_passes,
    ]);
}
}