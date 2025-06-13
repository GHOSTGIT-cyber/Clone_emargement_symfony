<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordReset;
use App\Enum\UserRole;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Filesystem\Filesystem;

class AdminUserController extends AbstractController
{
    #[Route('/admin/create-user', name: 'admin_create_user', methods: ['GET'])]
    public function showUserForm(): Response
    {
        return $this->render('admin/user_create.html.twig');
    }

    #[Route('/admin/create-user', name: 'admin_create_user_process', methods: ['POST'])]
public function createUser(
    Request $request,
    EntityManagerInterface $em,
    UserRepository $userRepository,
    UserPasswordHasherInterface $hasher,
    MailService $mailService
): Response {
    $usersData = $request->request->all('users');
    $successCount = 0;
    $errorCount = 0;

    foreach ($usersData as $data) {
        $firstname = trim($data['firstname'] ?? '');
        $lastname = trim($data['lastname'] ?? '');
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? '';

        if (empty($firstname) || empty($lastname) || empty($email) || empty($role)) {
            $errorCount++;
            continue;
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('error', "L'email $email est déjà utilisé.");
            $errorCount++;
            continue;
        }

        $usernameBase = strtolower($firstname . '.' . $lastname);
        $username = $usernameBase;
        $i = 1;
        while ($userRepository->findOneBy(['username' => $username])) {
            $username = $usernameBase . $i;
            $i++;
        }

        $tempPassword = bin2hex(random_bytes(6));
        $hashedPassword = $hasher->hashPassword(new User(), $tempPassword);

        $user = new User();
        $user->setUsername($username)
             ->setFirstname($firstname)
             ->setLastname($lastname)
             ->setEmail($email)
             ->setPassword($hashedPassword)
             ->setRole(UserRole::from($role));

        $em->persist($user);
        $em->flush();

        // Générer un token de réinitialisation
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTime('+1 hour');

        $reset = new PasswordReset();
        $reset->setUser($user)->setToken($token)->setExpiresAt($expiresAt);
        $em->persist($reset);
        $em->flush();

        $mailService->sendAccountActivationEmail($email, $username, $token);
        $successCount++;
    }

    if ($successCount > 0) {
        $this->addFlash('success', "$successCount utilisateur(s) créé(s) avec succès.");
    }

    if ($errorCount > 0) {
        $this->addFlash('error', "$errorCount utilisateur(s) n'ont pas pu être créés.");
    }

    return $this->redirectToRoute('admin_create_user');
}

#[Route('/admin/create-admin', name: 'admin_create_admin', methods: ['GET', 'POST'])]
public function createAdmin(
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher,
    MailService $mailService
): Response {
    $user = new User();

    // Création du formulaire lié à l'entité User
    $form = $this->createForm(AdminUserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if ($userRepository->findOneBy(['email' => $user->getEmail()])) {
            $this->addFlash('error', 'Un administrateur avec cet email existe déjà.');
            return $this->redirectToRoute('admin_create_admin');
        }

        // Générer un username unique
        $usernameBase = strtolower($user->getFirstname() . '.' . $user->getLastname());
        $username = $usernameBase;
        $i = 1;
        while ($userRepository->findOneBy(['username' => $username])) {
            $username = $usernameBase . $i;
            $i++;
        }

        $tempPassword = bin2hex(random_bytes(6));
        $hashedPassword = $hasher->hashPassword($user, $tempPassword);

        $user->setUsername($username)
             ->setPassword($hashedPassword)
             ->setRole(UserRole::ADMIN);

        $em->persist($user);
        $em->flush();

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTime('+1 hour');

        $reset = new PasswordReset();
        $reset->setUser($user)->setToken($token)->setExpiresAt($expiresAt);
        $em->persist($reset);
        $em->flush();

        $mailService->sendAccountActivationEmail($user->getEmail(), $username, $token);

        $this->addFlash('success', 'Administrateur créé et email d’activation envoyé.');
        return $this->redirectToRoute('admin_create_admin');
    }

    return $this->render('admin/admin_create.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/admin/parametres', name: 'admin_parametres')]
    public function parametres(Request $request, UserRepository $userRepo, PaginatorInterface $paginator): Response
{
    $query = $userRepo->createQueryBuilder('u')
        ->where('u.role = :role')
        ->setParameter('role', UserRole::ADMIN)
        ->orderBy('u.lastname', 'ASC');
    
        $limit = $request->query->getInt('limit', 5); // valeur par défaut : 10
        $search = $request->query->get('search', '');

        $queryBuilder = $userRepo->getAdminsQuery($search);

        $admins = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        $limit
    );

    $totalAdmins = $admins->getTotalItemCount();

    return $this->render('admin/parametres.html.twig', [
        'admins' => $admins,
        'totalAdmins' => $totalAdmins
    ]);
}

#[Route('/admin/profil', name: 'admin_profil')]
public function showProfil(): Response
{
    return $this->render('admin/profil.html.twig', [
        'user' => $this->getUser()
    ]);
}

#[Route('/admin/profil/update', name: 'admin_profil_update', methods: ['POST'])]
public function updateProfil(Request $request, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    $filesystem = new Filesystem();

    $user->setFirstname($request->request->get('firstname'));
    $user->setLastname($request->request->get('lastname'));
    $user->setEmail($request->request->get('email'));

    // Suppression de l’image si case cochée
    if ($request->request->has('delete_picture')) {
        $oldPicture = $user->getProfilePicture();
        if ($oldPicture) {
            $picturePath = $this->getParameter('kernel.project_dir') . '/public/' . $oldPicture;
            if ($filesystem->exists($picturePath)) {
                $filesystem->remove($picturePath);
            }
            $user->setProfilePicture(null);
        }
    }

    // Upload d’une nouvelle image si présente
    $uploadedFile = $request->files->get('profile_picture');
    if ($uploadedFile) {
        // Supprime l'ancienne photo si elle existe
        $oldPicture = $user->getProfilePicture();
        if ($oldPicture) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldPicture;
            if ($filesystem->exists($oldPath)) {
                $filesystem->remove($oldPath);
            }
        }

        $fileName = uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move($this->getParameter('uploads_directory'), $fileName);
        $user->setProfilePicture('uploads/' . $fileName);
    }

    $em->flush();
    $this->addFlash('success', 'Profil mis à jour avec succès.');

    return $this->redirectToRoute('admin_profil');
}
}