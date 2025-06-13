<?php

namespace App\Repository;

use App\Entity\Session;
use App\Entity\SignatureSession;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignatureSession>
 */
class SignatureSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignatureSession::class);
    }

    /**
     * Récupère toutes les signatures pour une session donnée
     */
    public function findBySession(Session $session)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.heureSignature', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de signatures par statut pour une session
     */
    public function countByStatut(Session $session, string $statut): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.user)')
            ->andWhere('s.session = :session')
            ->andWhere('s.statut = :statut')
            ->setParameter('session', $session)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les signatures non effectuées (sans heure de signature)
     */
    public function findNonSignees(Session $session)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->andWhere('s.heureSignature IS NULL')
            ->setParameter('session', $session)
            ->getQuery()
            ->getResult();
    }

    public function findSessionsPasseesNonJustifiees(User $user): array
{
    return $this->createQueryBuilder('s')
        ->join('s.session', 'sess')
        ->where('s.user = :user')
        ->andWhere('s.justifie = false OR s.justifie IS NULL')
        ->andWhere('sess.dateDebut < CURRENT_DATE()') // ✅ Champ correct
        ->setParameter('user', $user)
        ->orderBy('sess.dateDebut', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findSessionsFutures(User $user): array
{
    return $this->createQueryBuilder('s')
        ->join('s.session', 'sess')
        ->where('s.user = :user')
        ->andWhere('sess.dateDebut >= CURRENT_DATE()') // ✅ Champ correct
        ->setParameter('user', $user)
        ->orderBy('sess.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();
}

public function save(SignatureSession $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // Tu peux aussi ajouter une méthode remove si tu veux
    public function remove(SignatureSession $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}