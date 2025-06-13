<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Requête utilisée pour la pagination, avec gestion des filtres
     */
    public function getFormationsQuery(?string $dateDebut, ?string $dateFin, ?string $datePrecise, ?string $filter, ?string $search = null)
    {
        $qb = $this->createQueryBuilder('f');

        if ($datePrecise) {
            $date = new \DateTime($datePrecise);
            $start = (clone $date)->setTime(0, 0, 0);
            $end = (clone $date)->setTime(23, 59, 59);
            $qb->andWhere('f.createdAt BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        } elseif ($filter === 'week') {
            $startOfWeek = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
            $endOfWeek = (new \DateTimeImmutable('sunday this week'))->setTime(23, 59, 59);
            $qb->join('f.sessions', 's')
               ->andWhere('s.dateDebut BETWEEN :start AND :end OR s.dateFin BETWEEN :start AND :end')
               ->setParameter('start', $startOfWeek)
               ->setParameter('end', $endOfWeek)
               ->groupBy('f.id');
        } else {
            if ($dateDebut) {
                $qb->andWhere('f.createdAt >= :dateDebut')
                   ->setParameter('dateDebut', new \DateTime($dateDebut));
            }
            if ($dateFin) {
                $qb->andWhere('f.createdAt <= :dateFin')
                   ->setParameter('dateFin', new \DateTime($dateFin));
            }

     }

      if ($search) {
        $qb->andWhere('LOWER(f.nom) LIKE :search')
           ->setParameter('search', '%' . strtolower($search) . '%');

        }

        $qb->orderBy('f.createdAt', 'DESC');
        return $qb;
    }

    public function findWithSessionsThisWeek(): array
    {
        $startOfWeek = (new \DateTime())->modify('monday this week')->setTime(0, 0);
        $endOfWeek = (new \DateTime())->modify('sunday this week')->setTime(23, 59, 59);

        return $this->createQueryBuilder('f')
            ->join('f.sessions', 's')
            ->where('s.dateDebut BETWEEN :start AND :end OR s.dateFin BETWEEN :start AND :end')
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->groupBy('f.id')
            ->getQuery()
            ->getResult();
    }

    public function countFormations(): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFormationsToday(): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.createdAt BETWEEN :today AND :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }
}