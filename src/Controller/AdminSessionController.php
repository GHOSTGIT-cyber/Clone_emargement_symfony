<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Salle;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\Groupe; // ajoute ce use si ce n’est pas déjà fait
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;


class AdminSessionController extends AbstractController
{
    /**
     * Formulaire de création d'une session
     */
  #[Route('/admin/create-session', name: 'admin_create_session')]
public function createSessionForm(Request $request, EntityManagerInterface $em): Response
{
    $session = new Session();
    $form = $this->createForm(SessionType::class, $session);
    $form->handleRequest($request);

    $apprenants = $em->getRepository(User::class)->findBy(['role' => 'apprenant']);
    $groupes = $em->getRepository(Groupe::class)->findAll();
    $salles = $em->getRepository(Salle::class)->findAll();

    if ($form->isSubmitted() && $form->isValid()) {
        $choixApprenants = $request->request->get('choix_apprenants');
        $groupeIds = $request->request->all('groupe_id');
        $apprenantIds = $request->request->all('apprenants');

        // 👉 Gestion de la salle (non liée au formulaire Symfony)
        $salleNom = $form->get('salleNom')->getData();
        $salle = $em->getRepository(Salle::class)->findOneBy(['nom' => $salleNom]);
        if (!$salle) {
            $salle = new Salle();
            $salle->setNom($salleNom);
            $em->persist($salle);
        }
        $session->setSalle($salle);

        // 👉 Cas des groupes sélectionnés
        if ($choixApprenants === 'groupe' && !empty($groupeIds)) {
            foreach ($groupeIds as $groupeId) {
                $groupe = $em->getRepository(Groupe::class)->find($groupeId);
                if ($groupe) {
                    $session->addGroupe($groupe); // lien ManyToMany session_groupe
                    foreach ($groupe->getApprenants() as $apprenant) {
                        $session->addApprenant($apprenant);
                    }
                }
            }
        }

        // 👉 Cas des apprenants individuels
        if ($choixApprenants === 'individuel' && !empty($apprenantIds)) {
            foreach ($apprenantIds as $apprenantId) {
                $apprenant = $em->getRepository(User::class)->find($apprenantId);
                if ($apprenant) {
                    $session->addApprenant($apprenant);
                }
            }
        }

        $em->persist($session);
        $em->flush();

        $this->addFlash('success', 'Session créée avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }

    return $this->render('admin/create_session.html.twig', [
        'form' => $form->createView(),
        'apprenants' => $apprenants,
        'groupes' => $groupes,
        'salles' => $salles,
    ]);
}
    /**
     * Liste des sessions
     */
    #[Route('/admin/sessions', name: 'admin_sessions')]
public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
{
    $filter = $request->query->get('filter');
    $search = $request->query->get('search');
    $limit = $request->query->get('limit', 5); // Nombre d’éléments par page
    $now = new \DateTime();

    $sessionRepo = $em->getRepository(Session::class);
    $qb = $sessionRepo->createQueryBuilder('s');

    // recherche par nom de session
    if (!empty($search)) {
        $qb->andWhere('LOWER(s.nom) LIKE :search')
           ->setParameter('search', '%' . strtolower($search) . '%');
    }

    if ($filter === 'upcoming') {
        $qb->where('s.dateDebut > :now')
           ->setParameter('now', $now)
           ->orderBy('s.dateDebut', 'ASC');
    } elseif ($filter === 'past') {
        $qb->where('s.dateFin < :now')
           ->setParameter('now', $now)
           ->orderBy('s.dateDebut', 'DESC');
    } else {
        $qb->orderBy('s.dateDebut', 'DESC');
    }

    $sessions = $paginator->paginate(
        $qb,
        $request->query->getInt('page', 1),
        $limit // Nombre d’éléments par page
    );

    // Comptage pour les badges
    $totalAll = count($sessionRepo->findAll());
    $totalFuture = count($sessionRepo->createQueryBuilder('s')
        ->where('s.dateDebut > :now')->setParameter('now', $now)->getQuery()->getResult());
    $totalPast = count($sessionRepo->createQueryBuilder('s')
        ->where('s.dateFin < :now')->setParameter('now', $now)->getQuery()->getResult());

    return $this->render('admin/sessions.html.twig', [
        'sessions' => $sessions,
        'filter' => $filter,
        'search' => $search,
        'limit' => $limit,
        'totalAll' => $totalAll,
        'totalFuture' => $totalFuture,
        'totalPast' => $totalPast,
    ]);
}

#[Route('/admin/session/{id}/edit', name: 'admin_edit_session')]
public function editSession(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    SessionRepository $sessionRepo
): Response {
    $session = $sessionRepo->find($id);

    if (!$session) {
        throw $this->createNotFoundException('Session non trouvée.');
    }

    $form = $this->createForm(SessionType::class, $session);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Session mise à jour avec succès.');
        return $this->redirectToRoute('admin_sessions');
    }

    return $this->render('admin/edit_session.html.twig', [
        'form' => $form->createView(),
        'session' => $session
    ]);
}

#[Route('/admin/session/{id}/delete', name: 'admin_delete_session', methods: ['POST'])]
public function deleteSession(
    Session $session,
    Request $request,
    EntityManagerInterface $em
): Response {
    if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
        $em->remove($session);
        $em->flush();
        $this->addFlash('success', 'Session supprimée avec succès.');
    }

    return $this->redirectToRoute('admin_sessions');
}

#[Route('/admin/session/{id}/pdf', name: 'admin_session_pdf')]
public function generatePdf(Session $session, Environment $twig): Response
{
    // Config Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $dompdf = new Dompdf($options);

    // Render HTML via Twig
    $html = $twig->render('admin/session/pdf.html.twig', [
        'session' => $session,
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="session_' . $session->getId() . '.pdf"',
        ]
    );
}

#[Route('/admin/session/{id}', name: 'admin_show_session')]
public function show(
    Session $session,
    Request $request,
    PaginatorInterface $paginator,
    EntityManagerInterface $em
): Response {
    $search = $request->query->get('search');
    $limit = $request->query->get('limit', 5);
    $dateDebut = $request->query->get('date_debut');
    $dateFin = $request->query->get('date_fin');

    $qb = $em->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\SignatureSession', 's')
        ->join('s.user', 'u')
        ->where('s.session = :session')
        ->andWhere('u != :formateur') // 👈 exclusion du formateur
        ->setParameter('session', $session)
        ->setParameter('formateur', $session->getFormateur());

    if (!empty($search)) {
        $qb->andWhere('LOWER(u.firstname) LIKE :search OR LOWER(u.lastname) LIKE :search')
           ->setParameter('search', '%' . strtolower($search) . '%');
    }

    if (!empty($dateDebut)) {
        $qb->andWhere('s.heureSignature >= :dateDebut')
           ->setParameter('dateDebut', new \DateTime($dateDebut));
    }

    if (!empty($dateFin)) {
        $qb->andWhere('s.heureSignature <= :dateFin')
           ->setParameter('dateFin', (new \DateTime($dateFin))->setTime(23, 59, 59));
    }

    $signatures = $paginator->paginate(
        $qb,
        $request->query->getInt('page', 1),
        $limit
    );

    return $this->render('admin/show_session.html.twig', [
        'session' => $session,
        'signatures' => $signatures,
        'search' => $search,
        'limit' => $limit,
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
    ]);
}
}
