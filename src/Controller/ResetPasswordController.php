<?php

namespace App\Controller;

use App\Entity\PasswordReset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'reset_password')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // 1️⃣ On récupère le token dans les query parameters (?token=xxxx)
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'Lien de réinitialisation invalide.');
            return $this->redirectToRoute('forgot_password');
        }

        // 2️⃣ On cherche le token en base
        $resetRequest = $em->getRepository(PasswordReset::class)->findOneBy([
            'token' => $token
        ]);

        if (!$resetRequest || $resetRequest->isExpired()) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            return $this->redirectToRoute('forgot_password');
        }

        // 3️⃣ Si le formulaire est soumis
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Vérifications des mots de passe
            if (empty($newPassword) || strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            // 4️⃣ Mise à jour du mot de passe utilisateur
            $user = $resetRequest->getUser();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // 5️⃣ On supprime la demande de reset (le token)
            $em->remove($resetRequest);
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        // Affichage du formulaire
        return $this->render('reset_password/index.html.twig', [
            'token' => $token
            

        ]);
    }
}
