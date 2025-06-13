<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Groupe;

class AdminContactController extends AbstractController
{
    #[Route('/admin/contacts', name: 'admin_contacts')]
    public function contacts(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        $tab = $request->query->get('tab', 'apprenants');
        $filter = $request->query->get('filter', 'all');
        $search = strtolower(trim($request->query->get('search', '')));
        $limit = $request->query->getInt('limit', 5);

        $apprenants = [];
        $formateurs = [];
        $groupes = [];

        $totalAbsents = 0;
        $totalJustificatifs = 0;

        if ($tab === 'apprenants') {
            $qb = $userRepo->createQueryBuilder('u')
                ->where('u.role = :role')
                ->setParameter('role', 'apprenant');

            if ($search !== '') {
                $qb->andWhere('LOWER(u.firstname) LIKE :search OR LOWER(u.lastname) LIKE :search OR LOWER(u.email) LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }

            $apprenants = $paginator->paginate(
                $qb,
                $request->query->getInt('page', 1),
                $limit
            );

        } elseif ($tab === 'formateurs') {
            $qb = $userRepo->createQueryBuilder('u')
                ->where('u.role = :role')
                ->setParameter('role', 'formateur');

            if ($search !== '') {
                $qb->andWhere('LOWER(u.firstname) LIKE :search OR LOWER(u.lastname) LIKE :search OR LOWER(u.email) LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }

            $formateurs = $paginator->paginate(
                $qb,
                $request->query->getInt('page', 1),
                $limit
            );

        } elseif ($tab === 'groupes') {
            $qb = $em->getRepository(Groupe::class)->createQueryBuilder('g');

            if ($search !== '') {
                $qb->andWhere('LOWER(g.nom) LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }

            $groupes = $paginator->paginate(
                $qb,
                $request->query->getInt('page', 1),
                $limit
            );
        }

        return $this->render('admin/contacts.html.twig', [
            'tab' => $tab,
            'filter' => $filter,
            'search' => $search,
            'limit' => $limit,
            'apprenants' => $apprenants,
            'formateurs' => $formateurs,
            'groupes' => $groupes,
            'totalAbsents' => $totalAbsents,
            'totalJustificatifs' => $totalJustificatifs,
        ]);
    }
}