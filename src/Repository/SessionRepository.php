<?php

namespace App\Repository;

use App\Entity\Session;
use App\Entity\User;
use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use Doctrine\ORM\QueryBuilder;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * Compte les sessions dont la date de début est aujourd'hui
     */
    public function countSessionsToday(): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dateDebut >= :today')
            ->andWhere('s.dateDebut < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSessionsForFormation($formation): int
{
    return $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.formation = :formation')
        ->setParameter('formation', $formation)
        ->getQuery()
        ->getSingleScalarResult();
}

public function countSessionsForFormationThisWeek($formation): int
{
    $startOfWeek = (new \DateTime())->modify('monday this week')->setTime(0, 0);
    $endOfWeek = (new \DateTime())->modify('sunday this week')->setTime(23, 59, 59);

    return $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.formation = :formation')
        ->andWhere('s.dateDebut BETWEEN :start AND :end OR s.dateFin BETWEEN :start AND :end')
        ->setParameter('formation', $formation)
        ->setParameter('start', $startOfWeek)
        ->setParameter('end', $endOfWeek)
        ->getQuery()
        ->getSingleScalarResult();
}

public function countApprenantsByFormation(int $formationId): int
{
    $qb = $this->getEntityManager()->createQueryBuilder();

    $qb->select('COUNT(DISTINCT u.id)')
        ->from('App\Entity\User', 'u')
        ->leftJoin('u.sessions', 's') // sessions directes via session_apprenant
        ->leftJoin('u.groupes', 'g')  // groupes_apprenants
        ->leftJoin('g.sessions', 'sg') // sessions associées à ces groupes
        ->where('s.formation = :formationId OR sg.formation = :formationId')
        ->andWhere('u.role = :role')
        ->setParameter('formationId', $formationId)
        ->setParameter('role', 'apprenant');

    return (int) $qb->getQuery()->getSingleScalarResult();
}

/**
     * Trouve les sessions d'un formateur pour une période donnée
     * 
     * @param int $formateurId ID du formateur
     * @param DateTime $dateDebut Date de début de la période
     * @param DateTime|null $dateFin Date de fin de la période (null pour toutes les dates futures)
     * @return Session[] Retourne un tableau de sessions
     */
    public function findSessionsFormateurByDate(int $formateurId, DateTime $dateDebut, ?DateTime $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.formateur = :formateurId')
            ->setParameter('formateurId', $formateurId)
            ->andWhere('s.dateDebut >= :dateDebut')
            ->setParameter('dateDebut', $dateDebut);

        if ($dateFin) {
            $qb->andWhere('s.dateDebut <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        $qb->orderBy('s.dateDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findByGroupeAndTimeRange($groupe, \DateTimeInterface $start, \DateTimeInterface $end): array
{
    return $this->createQueryBuilder('s')
        ->leftJoin('s.groupes', 'g')
        ->where('g = :groupe')
        ->andWhere('s.dateDebut BETWEEN :start AND :end')
        ->setParameter('groupe', $groupe)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('s.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();
}

public function findByUserAndTimeRange(User $user, \DateTimeInterface $start, \DateTimeInterface $end)
{
    return $this->createQueryBuilder('s')
        ->join('s.apprenants', 'a')
        ->andWhere('a = :user')
        ->andWhere('s.dateDebut BETWEEN :start AND :end')
        ->setParameter('user', $user)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getResult();
}

public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
{
    return $this->createQueryBuilder('s')
        ->andWhere('s.dateDebut BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('s.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();
}

public function getSessionsByFormationQuery(
    Formation $formation,
    ?string $search = null,
    ?string $dateDebut = null,
    ?string $dateFin = null
): QueryBuilder {
    $qb = $this->createQueryBuilder('s')
        ->where('s.formation = :formation')
        ->setParameter('formation', $formation);

    if ($search) {
        $qb->andWhere('LOWER(s.nom) LIKE :search')
           ->setParameter('search', '%' . strtolower($search) . '%');
    }

    if ($dateDebut) {
        $qb->andWhere('s.dateDebut >= :dateDebut')
           ->setParameter('dateDebut', new \DateTime($dateDebut));
    }

    if ($dateFin) {
        $qb->andWhere('s.dateFin <= :dateFin')
           ->setParameter('dateFin', new \DateTime($dateFin));
    }

    return $qb->orderBy('s.dateDebut', 'DESC');
}
}


