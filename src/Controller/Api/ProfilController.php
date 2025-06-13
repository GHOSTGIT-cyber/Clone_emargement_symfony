<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/apprenant')]
#[IsGranted('ROLE_APPRENANT')]
class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'api_apprenant_profil_get', methods: ['GET'])]
    public function getProfil(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'username' => $user->getUsername(),
            'prenom' => $user->getFirstname(),
            'nom' => $user->getLastname(),
            'email' => $user->getEmail(),
            'photo' => $user->getProfilePicture(),
        ]);
    }

    #[Route('/profil', name: 'api_apprenant_profil_update', methods: ['POST'])]
    public function updateProfil(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');
        $remove = $request->request->getBoolean('removeProfilePicture', false);
        $photo = $request->files->get('profilePicture');

        if ($prenom) $user->setFirstname($prenom);
        if ($nom) $user->setLastname($nom);

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
                return $this->json(['success' => false, 'message' => 'Erreur lors de l’upload de la photo.'], 500);
            }
        }

        $em->flush();
        return $this->json(['success' => true, 'message' => 'Profil mis à jour.']);
    }

    #[Route('/profil/password', name: 'api_apprenant_password_update', methods: ['POST'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        //  Lecture du JSON brut envoyé dans le corps de la requête
        $data = json_decode($request->getContent(), true);

        $oldPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$oldPassword || !$newPassword) {
            return $this->json(['success' => false, 'message' => 'Champs manquants.'], 400);
        }

        if (!$hasher->isPasswordValid($user, $oldPassword)) {
            return $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 403);
        }

        $hashedPassword = $hasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Mot de passe mis à jour avec succès.']);
    }
}