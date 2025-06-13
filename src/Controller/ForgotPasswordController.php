<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordReset;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function index(Request $request, UserRepository $userRepo, EntityManagerInterface $em, MailService $mailService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            // Vérifie si l'utilisateur existe
            $user = $userRepo->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
                return $this->redirectToRoute('forgot_password');
            }

            // Générer le token et l'expiration
            $token = bin2hex(random_bytes(32));
            $expiresAt = new \DateTime('+1 hour');

            // À ce stade : insérer ou mettre à jour le token dans la table PasswordReset (Entity)
            // Supposons que tu as déjà une entité PasswordReset (sinon on la fait après)

             $reset = new PasswordReset();
             $reset->setUser($user);
             $reset->setToken($token);
             $reset->setExpiresAt($expiresAt);

             $em->persist($reset);
             $em->flush();

            // Envoi de l'email via ton service
            $mailService->sendResetPasswordEmail($email, $user->getUsername(), $token);

            $this->addFlash('success', 'Un email de réinitialisation vous a été envoyé !');

            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('forgot_password/index.html.twig');
    }
}
