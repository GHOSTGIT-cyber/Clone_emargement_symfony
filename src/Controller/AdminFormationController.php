<?php

namespace App\Controller;

use App\Form\FormationType;
use App\Entity\Formation;
use App\Repository\SessionRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;

class AdminFormationController extends AbstractController
{
    #[Route('/admin/formations', name: 'admin_formations')]
    public function index(
        Request $request,
        FormationRepository $formationRepo,
        SessionRepository $sessionRepo,
        PaginatorInterface $paginator
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $datePrecise = $request->query->get('date_precise');
        $filter = $request->query->get('filter');
        $search = $request->query->get('search');
        $limit = $request->query->getInt('limit', 5);

        $allFormations = $formationRepo->findAll();

        // Utiliser un unique QueryBuilder adapté aux filtres
        $queryBuilder = $formationRepo->getFormationsQuery($dateDebut, $dateFin, $datePrecise, $filter, $search);

        $formations = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit// nombre de formations par page
        );

        $totalThisWeek = count($formationRepo->findWithSessionsThisWeek());

        $sessionsCount = [];
        $apprenantsCount = [];

        // ⚠️ Important : $formations est une pagination (PaginationInterface)
        foreach ($formations as $formation) {
            $sessionsCount[$formation->getId()] = $filter === 'week'
                ? $sessionRepo->countSessionsForFormationThisWeek($formation->getId())
                : $sessionRepo->countSessionsForFormation($formation->getId());

            $apprenantsCount[$formation->getId()] = $sessionRepo->countApprenantsByFormation($formation->getId());
        }

        return $this->render('admin/formations.html.twig', [
            'formations' => $formations,
            'allFormations' => $allFormations,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'date_precise' => $datePrecise,
            'filter' => $filter,
            'search' => $search,
            'limit' => $limit,
            'totalThisWeek' => $totalThisWeek,
            'sessionsCount' => $sessionsCount,
            'apprenantsCount' => $apprenantsCount,
        ]);
    }

    #[Route('/admin/create-formation', name: 'admin_create_formation', methods: ['GET', 'POST'])]
    public function createFormation(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $em->getRepository(Formation::class)->findOneBy(['nom' => $formation->getNom()]);

            if ($existing) {
                $this->addFlash('error', 'Une formation avec ce nom existe déjà.');
                return $this->redirectToRoute('admin_create_formation');
            }

            $em->persist($formation);
            $em->flush();

            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('admin_formations');
        }

        return $this->render('admin/create_formation.html.twig', [
            'form' => $form->createView()
        ]);
    }

 #[Route('/admin/formation/{id}', name: 'admin_formation_show', methods: ['GET'])]
public function showFormation(
    int $id,
    FormationRepository $formationRepository,
    SessionRepository $sessionRepository,
    PaginatorInterface $paginator,
    Request $request
): Response {
    $formation = $formationRepository->find($id);
    if (!$formation) {
        throw $this->createNotFoundException("Formation non trouvée.");
    }

    $search = $request->query->get('search');
    $dateDebut = $request->query->get('date_debut');
    $dateFin = $request->query->get('date_fin');
    $limit = $request->query->getInt('limit', 5);

    $query = $sessionRepository->getSessionsByFormationQuery($formation, $search, $dateDebut, $dateFin);

    $sessions = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        $limit
    );

    return $this->render('admin/show_formation.html.twig', [
        'formation' => $formation,
        'sessions' => $sessions,
        'search' => $search,
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
        'limit' => $limit,
    ]);
}

#[Route('/admin/formation/{id}/edit', name: 'admin_edit_formation')]
public function editFormation(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    FormationRepository $formationRepo
): Response {
    $formation = $formationRepo->find($id);

    if (!$formation) {
        throw $this->createNotFoundException('Formation non trouvée.');
    }

    $form = $this->createForm(FormationType::class, $formation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Formation mise à jour avec succès.');
        return $this->redirectToRoute('admin_formation_show', ['id' => $formation->getId()]);
    }

    return $this->render('admin/edit_formation.html.twig', [
        'form' => $form->createView(),
        'formation' => $formation
    ]);
}

#[Route('/admin/formation/{id}/delete', name: 'admin_delete_formation', methods: ['POST'])]
public function deleteFormation(
    int $id,
    Request $request,
    FormationRepository $formationRepo,
    EntityManagerInterface $em
): Response {
    $formation = $formationRepo->find($id);

    if (!$formation) {
        throw $this->createNotFoundException("Formation non trouvée.");
    }

    if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->request->get('_token'))) {
        $em->remove($formation);
        $em->flush();
        $this->addFlash('success', 'Formation supprimée.');
    }

    return $this->redirectToRoute('admin_formations');
}

}