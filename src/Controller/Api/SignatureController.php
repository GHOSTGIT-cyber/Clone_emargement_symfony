<?php

namespace App\Controller\Api;

use App\Entity\Session;
use App\Entity\SignatureSession;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/apprenant', name: 'api_apprenant_')]
#[IsGranted('ROLE_APPRENANT')]
class SignatureController extends AbstractController
{
    #[Route('/signature/{id}', name: 'signature', methods: ['POST'])]
    public function enregistrerSignature(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $session = $entityManager->getRepository(Session::class)->find($id);
        if (!$session) {
            return $this->json(['success' => false, 'message' => 'Session non trouvée.'], 404);
        }

        if (!$session->getApprenants()->contains($user)) {
            return $this->json(['success' => false, 'message' => 'Vous ne participez pas à cette session.'], 403);
        }

        if (!$session->isActive()) {
            return $this->json(['success' => false, 'message' => 'La session n’est pas encore active.'], 400);
        }

        $now = new \DateTimeImmutable();

        if ($now < $session->getDateDebut() || $now > $session->getDateFin()) {
            return $this->json(['success' => false, 'message' => 'Vous ne pouvez signer que pendant la session.'], 400);
        }

        $existing = $entityManager->getRepository(SignatureSession::class)->findOneBy([
            'session' => $session,
            'user' => $user,
        ]);

        if ($existing && $existing->getSignatureData() !== null) {
            return $this->json(['success' => false, 'message' => 'Vous avez déjà signé pour cette session.'], 409);
        }

        $content = json_decode($request->getContent(), true);
        $dataUrl = $content['signatureData'] ?? null;

        if (!$dataUrl) {
            return $this->json(['success' => false, 'message' => 'Aucune signature détectée.'], 400);
        }

        $signature = $existing ?: new SignatureSession();
        $signature->setSession($session);
        $signature->setUser($user);
        $signature->setSignatureData($dataUrl);
        $signature->setHeureSignature($now);
        $signature->setStatut('présent');

        $entityManager->persist($signature);
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Signature enregistrée avec succès.']);
    }

    #[Route('/signature-session/{id}/delete', name: 'signature_delete', methods: ['DELETE'])]
    public function deleteSignature(int $id, EntityManagerInterface $em): JsonResponse
    {
        $signatureSession = $em->getRepository(SignatureSession::class)->find($id);

        if (!$signatureSession) {
            return $this->json(['success' => false, 'message' => 'Signature introuvable.'], 404);
        }

        $em->remove($signatureSession);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Signature supprimée.']);
    }

    #[Route('/signature/{id}', name: 'signature_info', methods: ['GET'])]
    public function getSignatureStatus(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $session = $em->getRepository(Session::class)->find($id);
        if (!$session) {
            return $this->json(['success' => false, 'message' => 'Session non trouvée.'], 404);
        }

        $signature = $em->getRepository(SignatureSession::class)->findOneBy([
            'session' => $session,
            'user' => $user,
        ]);

        return $this->json([
            'formation'     => $session->getFormation()?->getNom() ?? 'Sans titre',
            'formateur'     => $session->getFormateur()?->__toString() ?? 'Non assigné',
            'salle'         => $session->getSalle()?->getNom() ?? 'Salle à définir',
            'horaire'       => $session->getDateDebut()->format('H\hi') . ' - ' . $session->getDateFin()->format('H\hi'),
            'active'        => $session->isActive(),
            'alreadySigned' => $signature && $signature->getSignatureData() !== null,
        ]);
    }
}