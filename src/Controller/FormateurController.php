<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Entity\SignatureSession;
use App\Repository\SignatureSessionRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use \DateTime;

#[Route('/formateur')]
class FormateurController extends AbstractController
{
    private $security;
    private $entityManager;
    private $signatureSessionRepository;
    private $sessionRepository;

    public function __construct(
        Security $security, 
        EntityManagerInterface $entityManager,
        SignatureSessionRepository $signatureSessionRepository,
        SessionRepository $sessionRepository
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->signatureSessionRepository = $signatureSessionRepository;
        $this->sessionRepository = $sessionRepository;
    }

    #[Route('/dashboard', name: 'formateur_dashboard')]
    public function planning(): Response
    {
        // Récupération du formateur connecté
        /** @var User $formateur */
        $formateur = $this->security->getUser();
        
        if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        // Date du jour
        $aujourdhui = new DateTime('now');
        $aujourdhui->setTime(0, 0, 0);
        $demain = (new DateTime('now'))->modify('+1 day')->setTime(0, 0, 0);

        // Récupération des sessions du jour pour le formateur connecté
        $sessionsAujourdhui = $this->sessionRepository->createQueryBuilder('s')
            ->where('s.formateur = :formateur')
            ->andWhere('s.dateDebut >= :debut')
            ->andWhere('s.dateDebut < :fin')
            ->setParameter('formateur', $formateur)
            ->setParameter('debut', $aujourdhui)
            ->setParameter('fin', $demain)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        // Récupération des sessions futures pour le formateur connecté
        $sessionsAutresJours = $this->sessionRepository->createQueryBuilder('s')
            ->where('s.formateur = :formateur')
            ->andWhere('s.dateDebut >= :demain')
            ->setParameter('formateur', $formateur)
            ->setParameter('demain', $demain)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        // Préparation des données pour le template
        $coursAujourdhui = [];
        foreach ($sessionsAujourdhui as $session) {
            $coursAujourdhui[] = $this->formatSessionData($session);
        }

        $coursAutresJours = [];
        foreach ($sessionsAutresJours as $session) {
            $coursAutresJours[] = $this->formatSessionData($session);
        }

        return $this->render('formateur/dashboard.html.twig', [
            'cours_aujourdhui' => $coursAujourdhui,
            'cours_autres_jours' => $coursAutresJours,
        ]);
    }

    /**
     * Formate les données d'une session pour l'affichage
     */
    private function formatSessionData(Session $session): array
    {
        // Utilisation du repository pour récupérer les signatures
        $signatures = $this->signatureSessionRepository->findBySession($session);
        
        // Calcul du nombre de signatures obtenues
        $signaturesObtenues = 0;
        $signaturesTotal = count($signatures);
        
        foreach ($signatures as $signature) {
            if ($signature->getStatut() === 'présent' && $signature->getHeureSignature() !== null) {
                $signaturesObtenues++;
            }
        }

        // Détermination du badge (statut de la session)
        $badge = "À venir";
        $dateDebut = $session->getDateDebut();
        $dateFin = $session->getDateFin();
        $maintenant = new DateTime();
        
        if ($maintenant > $dateFin) {
            $badge = "Terminé";
        } elseif ($maintenant >= $dateDebut && $maintenant <= $dateFin) {
            $badge = "En cours";
        }

        // Formatage de l'horaire
        $horaire = $dateDebut->format('H:i') . ' - ' . $dateFin->format('H:i');
        
        return [
            'id' => $session->getId(),
            'titre' => $session->getNom(),
            'badge' => $badge,
            'horaire' => $horaire,
            'date' => $dateDebut->format('d/m/Y'),
            'apprenants' => count($session->getApprenants()),
            'salle' => $session->getSalle() ? $session->getSalle()->getNom() : 'Non définie',
            'signatures_obtenues' => $signaturesObtenues,
            'signatures_total' => $signaturesTotal
        ];
    }


    /**
 * Affiche l'historique des sessions passées du formateur connecté.
 */
#[Route('/formateur/historique', name: 'formateur_historique')]
public function historique(EntityManagerInterface $em): Response
{
    // Récupère l'utilisateur connecté (formateur)
    $formateur = $this->getUser();

    // Récupère toutes les sessions dont la date de fin est passée et où ce formateur était responsable
    $now = new \DateTimeImmutable();
    $sessionsPassees = $em->getRepository(Session::class)->createQueryBuilder('s')
        ->where('s.formateur = :formateur')
        ->andWhere('s.dateFin < :now')
        ->setParameter('formateur', $formateur)
        ->setParameter('now', $now)
        ->orderBy('s.dateDebut', 'DESC')
        ->getQuery()
        ->getResult();

    // Prépare les données pour le twig avec les mêmes clés/balises que dans planning.html.twig
    $historique = [];
    foreach ($sessionsPassees as $session) {
        $nbApprenants = 0;
        foreach ($session->getGroupes() as $groupe) {
        $nbApprenants += $groupe->getApprenants()->count();
        }
        
        $signatures = $em->getRepository(SignatureSession::class)->findBy(['session' => $session]);

        $historique[] = [
            'id' => $session->getId(),
            'titre' => $session->getNom(),
            'badge' => $session->getFormation() ? $session->getFormation()->getNom() : '',
            'horaire' => $session->getDateDebut()->format('H\hi') . ' - ' . $session->getDateFin()->format('H\hi'),
            'date' => $session->getDateDebut()->format('d/m/Y'),
            'apprenants' => $nbApprenants,
            'salle' => $session->getSalle() ? $session->getSalle()->getNom() : '',
            'signatures_obtenues' => count($signatures),
            'signatures_total' => $nbApprenants,
        ];
    }

    // Passe la liste à la vue
    return $this->render('formateur/historique.html.twig', [
        'historique' => $historique,
    ]);
}


}