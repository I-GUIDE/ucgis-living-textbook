<?php

namespace App\Repository;

use App\Entity\Concept;
use App\Entity\StudyArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConceptRepository extends ServiceEntityRepository
{
  public function __construct(RegistryInterface $registry)
  {
    parent::__construct($registry, Concept::class);
  }

  /**
   * @return array
   */
  public function findAllOrderedByName()
  {
    $qb = $this->createQueryBuilder('c')
        ->orderBy('c.name', 'ASC');

    $this->loadRelations($qb, 'c');

    return $qb->getQuery()->getResult();
  }

  /**
   * @param StudyArea $studyArea
   *
   * @return array
   */
  public function findByStudyAreaOrderedByName(StudyArea $studyArea)
  {
    $qb = $this->createQueryBuilder('c')
        ->where('c.studyArea = :studyArea')
        ->setParameter(':studyArea', $studyArea)
        ->orderBy('c.name', 'asc');

    $this->loadRelations($qb, 'c');

    return $qb->getQuery()->getResult();
  }

  /**
   * @param StudyArea $studyArea
   *
   * @return mixed
   * @throws NonUniqueResultException
   */
  public function getCountForStudyArea(StudyArea $studyArea)
  {
    return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->where('c.studyArea = :studyArea')
        ->setParameter('studyArea', $studyArea)
        ->getQuery()->getSingleScalarResult();
  }

  /**
   * Eagerly load the concept relations, while applying the soft deletable filter
   *
   * @param QueryBuilder $qb
   * @param string       $alias
   */
  private function loadRelations(QueryBuilder &$qb, string $alias)
  {
    $qb
        ->leftJoin($alias . '.outgoingRelations', 'r')
        ->leftJoin($alias . '.incomingRelations', 'ir')
        ->addSelect('r')
        ->addSelect('ir');
  }
}
