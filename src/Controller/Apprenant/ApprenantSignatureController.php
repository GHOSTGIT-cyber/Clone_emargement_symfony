<?php

namespace App\Controller\Apprenant;

use App\Entity\Session;
use App\Entity\SignatureSession;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApprenantSignatureController extends AbstractController
{
    #[Route('/apprenant/signature/{id}', name: 'apprenant_signature')]
    public function signature(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $session = $entityManager->getRepository(Session::class)->find($id);
        if (!$session) {
            throw $this->createNotFoundException('Session non trouvée.');
        }

        // Vérifie que l'utilisateur participe bien à la session
        if (!$session->getApprenants()->contains($user)) {
            throw $this->createAccessDeniedException('Vous ne participez pas à cette session.');
        }

        // Vérifie que la session est active
        if (!$session->isActive()) {
            $this->addFlash('error', 'La session n’est pas encore active.');
            return $this->redirectToRoute('apprenant_dashboard');
        }

        $now = new \DateTimeImmutable();

        // Vérifie que la signature est dans le bon créneau
        if ($now < $session->getDateDebut() || $now > $session->getDateFin()) {
            $this->addFlash('error', 'Vous ne pouvez signer que pendant la session.');
            return $this->redirectToRoute('apprenant_dashboard');
        }

        // Vérifie si une signature valide existe déjà
        $existing = $entityManager->getRepository(SignatureSession::class)->findOneBy([
            'session' => $session,
            'user' => $user,
        ]);

        if ($existing && $existing->getSignatureData() !== null) {
            $this->addFlash('warning', 'Vous avez déjà signé pour cette session.');
            return $this->redirectToRoute('apprenant_dashboard');
        }

        // Soumission du formulaire de signature
        if ($request->isMethod('POST')) {
            $dataUrl = $request->request->get('signatureData');

            if ($dataUrl) {
                $signature = $existing ?: new SignatureSession();
                $signature->setSession($session);
                $signature->setUser($user);
                $signature->setSignatureData($dataUrl);
                $signature->setHeureSignature($now);
                $signature->setStatut('présent');

                $entityManager->persist($signature);
                $entityManager->flush();

                $this->addFlash('success', 'Signature enregistrée avec succès.');
                return $this->redirectToRoute('apprenant_dashboard');
            }

            $this->addFlash('error', 'Aucune signature détectée.');
        }

        return $this->render('apprenant/apprenant_signature_canvas.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/signature-session/{id}/delete', name: 'signature_session_delete', methods: ['POST'])]
public function deleteSignature(
    int $id,
    EntityManagerInterface $em
): JsonResponse {
    $signatureSession = $em->getRepository(SignatureSession::class)->find($id);

    if (!$signatureSession) {
        return new JsonResponse(['success' => false, 'message' => 'Signature introuvable'], 404);
    }

    $em->remove($signatureSession);
    $em->flush();

    return new JsonResponse(['success' => true]);
}

}