<?php

namespace App\Controller\Api;

use App\Entity\SignatureSession;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'planning', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getPlanning(Request $request, SessionRepository $sessionRepository): JsonResponse
    {
        $user = $this->getUser();
        $role = $user->getRole()->value;

        $allowedRoles = ['apprenant', 'formateur', 'admin'];
        if (!in_array($role, $allowedRoles)) {
            return $this->json(['error' => 'Rôle utilisateur non autorisé.'], 403);
        }

        $startParam = $request->query->get('start');
        $endParam = $request->query->get('end');

        if (!$startParam || !$endParam) {
            return $this->json(['error' => 'Les paramètres start et end sont obligatoires.'], 400);
        }

        try {
            $start = new \DateTime($startParam);
            $end = new \DateTime($endParam);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide.'], 400);
        }

        $sessions = match ($role) {
            'apprenant' => $this->getSessionsForApprenant($sessionRepository, $user, $start, $end),
            'formateur' => $sessionRepository->findSessionsFormateurByDate($user->getId(), $start, $end),
            'admin'     => $sessionRepository->findByDateRange($start, $end),
        };

        $events = [];

        foreach ($sessions as $session) {
            $event = [
                'eventId' => $session->getId(),
                'title' => $role === 'apprenant'
                    ? $session->getFormation()?->getNom() ?? 'Sans titre'
                    : $session->getNom(),
                'start' => $session->getDateDebut()?->format('Y-m-d\TH:i:s'),
                'end'   => $session->getDateFin()?->format('Y-m-d\TH:i:s'),
                'salle' => $session->getSalle()?->getNom() ?? 'Salle à définir',
                'backgroundColor' => '#3788d8',
                'extendedProps' => [
                    'salle' => $session->getSalle()?->getNom() ?? 'Salle à définir',
                    'formateur' => $session->getFormateur()?->__toString() ?? null,
                    'justification' => null,
                    'role' => $role,
                ],
            ];

            if ($role === 'apprenant') {
                $signature = $session->getSignatures()
                    ->filter(fn(SignatureSession $s) => $s->getUser() === $user)
                    ->first();

                if ($signature && $signature->getStatut() === 'absent') {
                    $event['extendedProps']['justification'] = $signature->isJustifie()
                        ? 'Absence justifiée'
                        : 'Absence non justifiée';

                    $event['backgroundColor'] = $signature->isJustifie()
                        ? '#81c784'
                        : '#e57373';
                } elseif ($session->getDateDebut() > new \DateTime()) {
                    $event['backgroundColor'] = '#64b5f6';
                }
            }

            $events[] = $event;
        }

        return $this->json($events);
    }

    private function getSessionsForApprenant(SessionRepository $repo, $user, \DateTime $start, \DateTime $end): array
    {
        $byUser = $repo->findByUserAndTimeRange($user, $start, $end);

        $byGroup = [];
        foreach ($user->getGroupes() as $groupe) {
            $sessions = $repo->findByGroupeAndTimeRange($groupe, $start, $end);
            $byGroup = array_merge($byGroup, $sessions);
        }

        $cours = array_merge($byUser, $byGroup);

        $unique = [];
        foreach ($cours as $session) {
            $unique[$session->getId()] = $session;
        }

        return array_values($unique);
    }
}