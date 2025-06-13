<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/apprenant', name: 'api_apprenant_')]
#[IsGranted('ROLE_APPRENANT')]
class SessionController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(SessionRepository $sessionRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $groupes = $user->getGroupes();

        $now = new \DateTimeImmutable();
        $todayStart = new \DateTimeImmutable('today midnight');
        $todayEnd = $todayStart->setTime(23, 59, 59);
        $pastWeekStart = $todayStart->modify('-7 days');

        $cours_aujourdhui = [];
        $cours_passes = [];

        // Sessions par groupe
        foreach ($groupes as $groupe) {
            $sessionsDuGroupe = $sessionRepository->findByGroupeAndTimeRange($groupe, $todayStart, $todayEnd);
            foreach ($sessionsDuGroupe as $session) {
                $data = [
                    'formation' => $session->getFormation()?->getNom() ?? 'Sans titre',
                    'formateur' => $session->getFormateur()?->__toString() ?? 'Non assigné',
                    'salle'     => $session->getSalle()?->getNom() ?? 'Salle à définir',
                    'horaire'   => $session->getDateDebut()->format('H\hi') . ' - ' . $session->getDateFin()->format('H\hi'),
                    'active'    => $session->isActive(), 
                    'id'        => $session->getId(),
                ];

                if ($session->getDateFin() > $now) {
                    $cours_aujourdhui[] = $data;
                } else {
                    $cours_passes[] = $data;
                }
            }

            $sessionsPassees = $sessionRepository->findByGroupeAndTimeRange($groupe, $pastWeekStart, $todayStart->modify('-1 second'));
            foreach ($sessionsPassees as $session) {
                $cours_passes[] = [
                    'formation' => $session->getFormation()?->getNom() ?? 'Sans titre',
                    'horaire'   => $session->getDateDebut()->format('d/m/Y H\hi') . ' à ' . $session->getDateFin()->format('H\hi'),
                    'id'        => $session->getId(), 
                ];
            }
        }

        // Sessions individuelles
        $sessionsIndividuelles = $sessionRepository->findByUserAndTimeRange($user, $todayStart, $todayEnd);
        foreach ($sessionsIndividuelles as $session) {
            $data = [
                'formation' => $session->getFormation()?->getNom() ?? 'Sans titre',
                'formateur' => $session->getFormateur()?->__toString() ?? 'Non assigné',
                'salle'     => $session->getSalle()?->getNom() ?? 'Salle à définir',
                'horaire'   => $session->getDateDebut()->format('H\hi') . ' - ' . $session->getDateFin()->format('H\hi'),
                'active'    => $session->isActive(), 
                'id'        => $session->getId(),
            ];

            if ($session->getDateFin() > $now) {
                $cours_aujourdhui[] = $data;
            } else {
                $cours_passes[] = $data;
            }
        }

        $sessionsPasseesIndiv = $sessionRepository->findByUserAndTimeRange($user, $pastWeekStart, $todayStart->modify('-1 second'));
        foreach ($sessionsPasseesIndiv as $session) {
            $cours_passes[] = [
                'formation' => $session->getFormation()?->getNom() ?? 'Sans titre',
                'horaire'   => $session->getDateDebut()->format('d/m/Y H\hi') . ' à ' . $session->getDateFin()->format('H\hi'),
                'id'        => $session->getId(), 
            ];
        }

        return $this->json([
            'cours_aujourdhui' => $cours_aujourdhui,
            'cours_passes' => $cours_passes,

        ]);
    }
}