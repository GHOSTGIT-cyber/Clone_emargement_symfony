<?php

namespace App\Controller\Apprenant;

use App\Entity\User;
use App\Form\UserEditType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormError;

class ApprenantProfilController extends AbstractController
{
   #[Route('/apprenant/profil', name: 'apprenant_profil')]
#[IsGranted('ROLE_APPRENANT')]
public function profil(
    Request $request,
    EntityManagerInterface $em,
    SluggerInterface $slugger,
    UserPasswordHasherInterface $hasher
): Response {
    $user = $this->getUser();

    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à votre profil.');
    }

    // Formulaire d'édition de profil
    $form = $this->createForm(UserEditType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $photo = $form->get('profilePicture')->getData();
        $remove = $form->get('removeProfilePicture')->getData();

        if ($remove && $user->getProfilePicture()) {
        $filePath = $this->getParameter('uploads_directory') . '/' . $user->getProfilePicture();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $user->setProfilePicture(null);
    }

        if ($photo) {
            $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $photo->guessExtension();

            try {
                $photo->move($this->getParameter('uploads_directory'), $newFilename);
                $user->setProfilePicture($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’upload de l’image.');
            }
        }

        $em->flush();
        $this->addFlash('success', 'Profil mis à jour.');
        return $this->redirectToRoute('apprenant_profil');
    }

    // Formulaire de changement de mot de passe
    $changePasswordForm = $this->createForm(ChangePasswordType::class);
    $changePasswordForm->handleRequest($request);

    if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
        $oldPassword = $changePasswordForm->get('currentPassword')->getData();

        if (!$hasher->isPasswordValid($user, $oldPassword)) {
            $changePasswordForm->get('currentPassword')->addError(new FormError('Mot de passe actuel incorrect.'));
        } else {
            $newPassword = $changePasswordForm->get('plainPassword')->getData();
            $hashedPassword = $hasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
            return $this->redirectToRoute('apprenant_profil');
        }
    }

    return $this->render('apprenant/apprenant_profil.html.twig', [
        'user' => $user,
        'form' => $form->createView(),
        'changePasswordForm' => $changePasswordForm->createView(),
    ]);
}
}