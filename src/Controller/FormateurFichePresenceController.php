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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use \DateTime;
use App\Service\MailService;

class FormateurFichePresenceController extends AbstractController
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

    #[Route('/fiche-presence/{id}', name: 'formateur_fiche_presence')]
    public function fichePresence(Request $request, int $id): Response
    {
        // 1. Récupération du formateur connecté
        /** @var User $formateur */
        $formateur = $this->security->getUser();

        if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Récupération de la session
        $session = $this->sessionRepository->find($id);

        if (!$session) {
            throw $this->createNotFoundException('La session demandée n\'existe pas');
        }

        // 3. Vérifier que le formateur est bien associé à cette session
        if ($session->getFormateur() !== $formateur) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette session');
        }

        // 4. Vérifier si le formateur a déjà signé
        $signatureFormateur = $this->signatureSessionRepository->findOneBy([
            'session' => $session,
            'user' => $formateur
        ]);
        $formateurADejaSigné = $signatureFormateur && $signatureFormateur->getSignatureData();

        // 5. Récupération des signatures pour cette session
        $signatures = $this->signatureSessionRepository->findBySession($session);

        // ----- PATCH OBLIGATOIRE POUR TWIG -----
        // Certains résultats Doctrine peuvent être des tableaux, donc on extrait toujours la SignatureSession.
        $signatures = array_map(function($item) {
            // Si c'est un array dont le premier élément est SignatureSession
            if (is_array($item) && isset($item[0]) && $item[0] instanceof \App\Entity\SignatureSession) {
                return $item[0];
            }
            // Si c'est déjà un objet SignatureSession, on le garde
            return $item;
        }, $signatures);

        // On filtre pour ne garder QUE les vrais objets SignatureSession (jamais d'array, jamais null)
        $signatures = array_filter($signatures, function($item) {
            return $item instanceof \App\Entity\SignatureSession;
        });
        // ----- FIN PATCH -----
       
        //  On exclut ici le formateur
        $signatures = array_filter($signatures, function($item) use ($formateur) {
        return $item->getUser() !== $formateur;
        });
        
        // 6. Calcul du nombre de signatures
        $signaturesObtenues = 0;
        $signaturesTotal = count($signatures);

        foreach ($signatures as $signature) {
            if ($signature->getStatut() === 'présent' && $signature->getHeureSignature() !== null) {
                $signaturesObtenues++;
            }
        }

        // 7. Formatage des données pour la vue
        $sessionData = [
            'id' => $session->getId(),
            'titre' => $session->getNom(),
            'date' => $session->getDateDebut()->format('d/m/Y'),
            'horaire' => $session->getDateDebut()->format('H:i') . ' - ' . $session->getDateFin()->format('H:i'),
            'signatures_obtenues' => $signaturesObtenues,
            'signatures_total' => $signaturesTotal,
            'formateur' => $formateur->getFirstName() . ' ' . $formateur->getLastName()
        ];

        $apprenantsFiltres = [];

        foreach ($session->getApprenants() as $user) {
            if (in_array('ROLE_APPRENANT', $user->getRoles())) {
                $apprenantsFiltres[] = $user;
            }
        }

        // 8. Affichage de la vue
        return $this->render('formateur/fiche_presence.html.twig', [
            'session' => $sessionData,
            'titre' => $session,
            'signatures' => $signatures,
            'formateur' => $formateur,
            'formateur_a_deja_signe' => $formateurADejaSigné,
            'apprenants' => $apprenantsFiltres
        ]);
    }

    #[Route('/sauvegarder-signature/{id}', name: 'formateur_sauvegarder_signature', methods: ['POST'])]
    public function sauvegarderSignature(Request $request, int $id): JsonResponse
    {
        /** @var User $formateur */
        $formateur = $this->security->getUser();
        
        if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['success' => false, 'message' => 'Session non trouvée'], 404);
        }

        // Récupérer les données de signature depuis la requête
        $signatureData = $request->request->get('signature_data');
        
        if (!$signatureData) {
            return new JsonResponse(['success' => false, 'message' => 'Données de signature manquantes'], 400);
        }

        // Chercher ou créer la signature du formateur
        $signatureFormateur = $this->signatureSessionRepository->findOneBy([
            'session' => $session,
            'user' => $formateur
        ]);

        if (!$signatureFormateur) {
            $signatureFormateur = new SignatureSession();
            $signatureFormateur->setSession($session);
            $signatureFormateur->setUser($formateur);
        }

        // Sauvegarder la signature
        $signatureFormateur->setSignatureData($signatureData);
        $signatureFormateur->setStatut('présent');
        $signatureFormateur->setHeureSignature(new \DateTime());

        $this->entityManager->persist($signatureFormateur);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Signature sauvegardée avec succès']);
    }

    /**
     * Active la session de signature et prépare l'envoi des mails aux apprenants sélectionnés.
     * La session devient alors "active" côté apprenant.
     */
    #[Route('/formateur/session/{id}/envoyer-mail-signature', name: 'formateur_envoyer_email_signature', methods: ['POST'])]
    public function envoyerMailSignature(
        int $id,
        Request $request,
        SessionRepository $sessionRepository,
         SignatureSessionRepository $signatureSessionRepository,
          MailService $mailService,
        EntityManagerInterface $em
        // MailerInterface $mailer // Décommente pour envoyer les mails plus tard
    ): Response {

        
        // 1. Récupération de la session
        $session = $em->getRepository(Session::class)->find($id);

        if (!$session) {
            $this->addFlash('danger', 'Session introuvable.');
            return $this->redirectToRoute('formateur_planning');
        }

        // 2. Récupère les IDs des apprenants sélectionnés dans le formulaire (checkbox)
        $apprenantsIds = $request->request->all('apprenants'); // Récupère un array ou []
        if (!is_array($apprenantsIds)) {
            $apprenantsIds = [];
        }

        $apprenants = [];
        foreach ($apprenantsIds as $userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                $apprenants[] = $user;
            }
        }

        // 3. Active la session pour la signature des apprenants
        $session->setActive(true);

        // 4. (Optionnel) Ici tu pourrais créer ou mettre à jour les SignatureSession pour les apprenants sélectionnés

        // 5. Enregistre les modifications en base de données
        $em->flush();

        // 6. Prépare le code d'envoi de mail (à activer plus tard)
        /*
        foreach ($apprenants as $apprenant) {
            // $email = (new Email())
            //     ->from('no-reply@gefor.fr')
            //     ->to($apprenant->getEmail())
            //     ->subject('Signature de présence requise')
            //     ->text('Merci de signer la feuille de présence via le lien...');
            // $mailer->send($email);
        }
        */

        foreach ($apprenants as $apprenant) {
        $mailService->sendPresenceNotificationEmail(
            $apprenant->getEmail(),
            $apprenant->getFirstname(), // ou getPrenom()
            $session->getFormation()->getNom(),
            $session->getDateDebut()
        );
    }

        // 7. Feedback à l'utilisateur
        $this->addFlash('success', 'Session activée. Un email a été envoyé aux apprenants sélectionnés.');

        // 8. Redirige vers la fiche de présence
        return $this->redirectToRoute('formateur_fiche_presence', ['id' => $id]);
    }

    // --- SUPPRIMER UNE SIGNATURE ---
    #[Route('/signature-session/{id}/delete', name: 'signature_session_delete', methods: ['POST'])]
    public function deleteSignature(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        /** @var User $formateur */
    $formateur = $this->getUser();
    if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
        throw $this->createAccessDeniedException('Accès refusé');
    }
        // Vérifie le token CSRF envoyé dans le form
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_signature_' . $id, $submittedToken)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('formateur_fiche_presence', ['id' => $request->get('session_id')]);
        }
        // Trouve et supprime la signature
        $signatureSession = $em->getRepository(\App\Entity\SignatureSession::class)->find($id);
        if ($signatureSession) {
        $signatureSession->setSignatureData(null);
        $signatureSession->setHeureSignature(null);
        $signatureSession->setStatut(null);
        $signatureSession->setCommentaire(null); // Facultatif : tu peux garder le commentaire si tu veux
        $em->flush();

        $this->addFlash('success', 'Signature de l\'apprenant réinitialisée.');
    }else {
            $this->addFlash('danger', 'Signature introuvable.');
        }
        return $this->redirectToRoute('formateur_fiche_presence', [
            'id' => $request->get('session_id')
        ]);
    }

    // --- MARQUER ABSENCE JUSTIFIÉE ---
    #[Route('/signature-session/{id}/justified', name: 'signature_session_justified', methods: ['POST'])]
    public function justifiedSignature(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('justified_signature_' . $id, $submittedToken)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('formateur_fiche_presence', ['id' => $request->get('session_id')]);
        }
        $signatureSession = $em->getRepository(\App\Entity\SignatureSession::class)->find($id);
        if ($signatureSession) {
            $signatureSession->setStatut('absent');
            $signatureSession->setJustifie(true);
            $em->flush();
            $this->addFlash('success', 'Apprenant marqué comme absence justifiée.');
        } else {
            $this->addFlash('danger', 'Signature introuvable.');
        }
        return $this->redirectToRoute('formateur_fiche_presence', [
            'id' => $request->get('session_id')
        ]);
    }

    // === SIGNATURE MANUELLE POUR FORMATEUR - VERSION CORRIGÉE ===
    #[Route('/formateur/signature-session/{id}/manual-sign', name: 'formateur_signature_manual_sign', methods: ['POST'])]
    public function formateurManualSign(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est bien un formateur
            /** @var User $formateur */
            $formateur = $this->security->getUser();
            
            if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
            }
            
            // Récupérer la SignatureSession manuellement
            $signatureSession = $entityManager->getRepository(SignatureSession::class)->find($id);
            
            if (!$signatureSession) {
                return new JsonResponse(['success' => false, 'message' => 'Signature introuvable'], 404);
            }
            
            // Vérifier que le formateur a le droit de modifier cette signature
            $session = $signatureSession->getSession();
            if ($session->getFormateur() !== $formateur) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé pour cette session'], 403);
            }
            
            $signatureData = $request->request->get('signature_data');
            
            if (!$signatureData) {
                return new JsonResponse(['success' => false, 'message' => 'Données de signature manquantes'], 400);
            }
            
            // Enregistrer la signature
            $signatureSession->setSignatureData($signatureData);
            $signatureSession->setStatut('présent');
            $signatureSession->setHeureSignature(new \DateTime());
            
            $entityManager->persist($signatureSession);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Signature manuelle enregistrée avec succès']);
            
        } catch (\Exception $e) {
            // Log détaillé pour débugger
            error_log('[ERREUR] Signature manuelle formateur - ID: ' . $id . ' - Erreur: ' . $e->getMessage());
            error_log('[ERREUR] Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    // === RETARD - VERSION CORRIGÉE ===
    #[Route('/signature-session/{id}/late', name: 'signature_session_late', methods: ['POST'])]
    public function markLate(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est bien un formateur
            /** @var User $formateur */
            $formateur = $this->security->getUser();
            
            if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
            }
            
            // Récupérer la SignatureSession manuellement
            $signatureSession = $entityManager->getRepository(SignatureSession::class)->find($id);
            
            if (!$signatureSession) {
                return new JsonResponse(['success' => false, 'message' => 'Signature introuvable'], 404);
            }
            
            // Vérifier que le formateur a le droit de modifier cette signature
            $session = $signatureSession->getSession();
            if ($session->getFormateur() !== $formateur) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé pour cette session'], 403);
            }
            
            // Récupération du paramètre 'motif'
            $motif = $request->request->get('motif');
            
            if (!$motif || trim($motif) === '') {
                return new JsonResponse(['success' => false, 'message' => 'Motif de retard manquant'], 400);
            }
            
            $signatureSession->setStatut('retard');
            $signatureSession->setMotifRetard($motif);
            $signatureSession->setHeureSignature(new \DateTime());
            
            $entityManager->persist($signatureSession);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Retard enregistré avec succès']);
            
        } catch (\Exception $e) {
            error_log('[ERREUR] Retard - ID: ' . $id . ' - Erreur: ' . $e->getMessage());
            error_log('[ERREUR] Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    // === COMMENTAIRE - VERSION CORRIGÉE ===
    #[Route('/signature-session/{id}/comment', name: 'signature_session_comment', methods: ['POST'])]
    public function addComment(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est bien un formateur
            /** @var User $formateur */
            $formateur = $this->security->getUser();
            
            if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
            }
            
            // Récupérer la SignatureSession manuellement
            $signatureSession = $entityManager->getRepository(SignatureSession::class)->find($id);
            
            if (!$signatureSession) {
                return new JsonResponse(['success' => false, 'message' => 'Signature introuvable'], 404);
            }
            
            // Vérifier que le formateur a le droit de modifier cette signature
            $session = $signatureSession->getSession();
            if ($session->getFormateur() !== $formateur) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé pour cette session'], 403);
            }
            
            $commentaire = $request->request->get('commentaire');
            
            if (!$commentaire || trim($commentaire) === '') {
                return new JsonResponse(['success' => false, 'message' => 'Commentaire manquant'], 400);
            }
            
            $signatureSession->setCommentaire($commentaire);
            
            $entityManager->persist($signatureSession);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Commentaire ajouté avec succès']);
            
        } catch (\Exception $e) {
            error_log('[ERREUR] Commentaire - ID: ' . $id . ' - Erreur: ' . $e->getMessage());
            error_log('[ERREUR] Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }


    // === DÉPART ANTICIPÉ - NOUVELLE ROUTE ===
    #[Route('/signature-session/{id}/early-departure', name: 'signature_session_early_departure', methods: ['POST'])]
    public function markEarlyDeparture(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est bien un formateur
            /** @var User $formateur */
            $formateur = $this->security->getUser();
            
            if (!$formateur || !in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
            }
            
            // Récupérer la SignatureSession manuellement
            $signatureSession = $entityManager->getRepository(SignatureSession::class)->find($id);
            
            if (!$signatureSession) {
                return new JsonResponse(['success' => false, 'message' => 'Signature introuvable'], 404);
            }
            
            // Vérifier que le formateur a le droit de modifier cette signature
            $session = $signatureSession->getSession();
            if ($session->getFormateur() !== $formateur) {
                return new JsonResponse(['success' => false, 'message' => 'Non autorisé pour cette session'], 403);
            }
            
            // Récupération du motif (optionnel)
            $motif = $request->request->get('motif');
            
            // Enregistrer l'heure de départ anticipé
            $signatureSession->setHeureDepart(new \DateTime());
            
            // Si un motif est fourni, l'enregistrer dans motifDetails
            if ($motif && trim($motif) !== '') {
                $signatureSession->setMotifDetails($motif);
            }
            
            $entityManager->persist($signatureSession);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true, 
                'message' => 'Départ anticipé enregistré avec succès',
                'heureDepart' => $signatureSession->getHeureDepart()->format('H:i')
            ]);
            
        } catch (\Exception $e) {
            error_log('[ERREUR] Départ anticipé - ID: ' . $id . ' - Erreur: ' . $e->getMessage());
            error_log('[ERREUR] Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
}