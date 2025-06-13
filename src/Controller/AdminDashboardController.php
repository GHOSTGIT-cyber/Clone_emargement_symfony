<?php

namespace App\Controller;


use App\Entity\Session;
use App\Entity\Formation;
use App\Repository\SessionRepository;
use App\Repository\SignatureSessionRepository; // ajouté pour les présences
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        EntityManagerInterface $em,
        FormationRepository $formationRepo,
        SessionRepository $sessionRepo,

        SignatureSessionRepository $signatureRepo, // pour récupérer les présences ou signatures

    ): Response
    
    {
        // 1️ Récupérer l'utilisateur connecté
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        

        // 2️ Données basiques pour la vue
        $firstname = $user->getFirstname();
        $profilePicture = $user->getProfilePicture();
        $userInitials = strtoupper(substr($firstname, 0, 1));

        // 3️ Appels à la BDD (créer des repositories dédiés pour ça)
        $apprenants = $em->getRepository('App\Entity\User')->count(['role' => 'apprenant']);
        $formateurs = $em->getRepository('App\Entity\User')->count(['role' => 'formateur']);
        $admins = $em->getRepository('App\Entity\User')->count(['role' => 'admin']);
        $totalUsers = $apprenants + $formateurs + $admins;
        
        $sessions = $em->getRepository(Session::class)->count([]);
        $sessionsToday = $sessionRepo->countSessionsToday();
        
        $presences = $signatureRepo->count(['statut' => 'présent']);
        $retards = $signatureRepo->count(['statut' => 'retard']);
        $absences = $signatureRepo->count(['statut' => 'absent']);

        $totalSignatures = $signatureRepo->count([]);

        $presenceStats = [ 
            'presence' => $presences,
            'retard' => $retards,
            'absence' => $absences,
            'total' => $totalSignatures,
            'percent' => [
                'presence' => $totalSignatures > 0 ? round(($presences / $totalSignatures) * 100, 1) : 0, //? expression ternaire pour dire si totalsignature > 0 on fait le calcul sinon en retourne 0
                'retard' => $totalSignatures > 0 ? round(($retards / $totalSignatures) * 100, 1) : 0,
                'absence' => $totalSignatures > 0 ? round(($absences / $totalSignatures) * 100, 1) : 0,
            ]
        ];



        //  Formations avec le repo dédié
        $formationsToday = $formationRepo->countFormationsToday();
        $totalFormations = $formationRepo->countFormations();


        return $this->render('admin/dashboard.html.twig', [
            'firstname' => $firstname,
            'profilePicture' => $profilePicture,
            'userInitials' => $userInitials,
            'apprenants' => $apprenants,
            'formateurs' => $formateurs,
            'sessions' => $sessions,
            'sessionsToday' => $sessionsToday,
            'formationsToday' => $formationsToday,
            'totalFormations' => $totalFormations,
            'currentDate' => (new \DateTime())->format('d/m/Y'),
            'totalUsers' => $totalUsers,
            'presenceStats' => $presenceStats
        ]);
    }





}


