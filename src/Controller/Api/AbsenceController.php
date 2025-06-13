<?php

namespace App\Controller\Api;

use App\Repository\SignatureSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/apprenant', name: 'api_apprenant_')]
#[IsGranted('ROLE_APPRENANT')]
class AbsenceController extends AbstractController
{
    #[Route('/justifier-absence', name: 'justifier_absence', methods: ['POST'])]
    public function justifierAbsence(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        SignatureSessionRepository $signatureRepo
    ): JsonResponse {
        $user = $this->getUser();

        // Récupère les données JSON ou formulaire
        $passees = $request->request->all('sessionsPassees') ?? [];
        $futures = $request->request->all('sessionsFutures') ?? [];
        $motif = $request->request->get('motifDetails');
        $document = $request->files->get('document');

        $filename = null;
        if ($document) {
            $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $filename = $safeFilename . '-' . uniqid() . '.' . $document->guessExtension();

            try {
                $document->move($this->getParameter('justifications_directory'), $filename);
            } catch (FileException $e) {
                return $this->json(['success' => false, 'message' => 'Erreur lors de l\'upload.'], 500);
            }
        }

        // Fusionne et filtre les sessions
        $ids = array_merge($passees, $futures);
        if (count($ids) === 0) {
            return $this->json(['success' => false, 'message' => 'Aucune session sélectionnée.'], 400);
        }

        $signatures = $signatureRepo->findBy([
            'id' => $ids,
            'user' => $user,
        ]);

        foreach ($signatures as $signature) {
            $signature->setJustifie(true);
            $signature->setMotifAbsence('justifié');
            $signature->setMotifDetails($motif);
            if ($filename) {
                $signature->setDocument($filename);
            }
            $em->persist($signature);
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Justification enregistrée.']);
    }

    
#[Route('/absence/sessions', name: 'sessions_absences', methods: ['GET'])]
public function getSessionsAbsence(
    SignatureSessionRepository $repo
): JsonResponse {
    $user = $this->getUser();

    $sessionsPassees = $repo->findSessionsPasseesNonJustifiees($user);
    $sessionsFutures = $repo->findSessionsFutures($user);

    $format = fn($s) => [
        'id' => $s->getId(),
        'formation' => $s->getSession()->getFormation()?->getNom(),
        'date' => $s->getSession()->getDateDebut()->format('Y-m-d H:i')
    ];

    return $this->json([
        'success' => true,
        'sessionsPassees' => array_map($format, $sessionsPassees),
        'sessionsFutures' => array_map($format, $sessionsFutures)
    ]);
}
}