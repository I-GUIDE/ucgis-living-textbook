<?php

namespace App\Repository;

use App\Entity\Concept;
use App\Entity\StudyArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Drenso\Shared\Database\RepositoryTraits\FindIdsTrait;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @extends ServiceEntityRepository<Concept> */
class ConceptRepository extends ServiceEntityRepository
{
  use FindIdsTrait;

  public function __construct(
    ManagerRegistry $registry,
    #[Autowire('%study_area_slug%')] private readonly string $studyAreaSlug)
  {
    parent::__construct($registry, Concept::class);
  }

  public function findOneByIdOrSlug(string|int|StudyArea $studyArea, string $conceptId): ?Concept
  {

    $qb = $this->createQueryBuilder('c')
      ->where('c.deletedAt IS NULL');

    if ($studyArea == $this->studyAreaSlug) {
      $latestPublicStudyAreaSubQuery = $this->getEntityManager()->createQueryBuilder()
        ->select('sa2')
        ->from(StudyArea::class, 'sa2')
        ->where('sa2.accessType = :publicAccessType')
        ->andWhere('sa2.openAccess = true')
        ->andWhere('sa2.deletedAt IS NULL')
        ->orderBy('sa2.createdAt', 'DESC')
        ->setMaxResults(1);


      $qb->andWhere('c.studyArea = (' . $latestPublicStudyAreaSubQuery->getDQL() . ')')
        ->setParameter('publicAccessType', 'public');


    } else {
      $qb->andWhere('c.studyArea = :studyArea')
         ->setParameter('studyArea', is_numeric($studyArea) ? (int)$studyArea : $studyArea);
    }



    if (ctype_digit($conceptId)) {
      $qb->andWhere('c.id = :id')
        ->setParameter('id', (int)$conceptId);
    } else {
      $qb->andWhere('LOWER(c.slug) = LOWER(:slug)')
        ->setParameter('slug', $conceptId);
    }

    /** @var Concept|null $concept */
    $concept = $qb->getQuery()->getOneOrNullResult();

    return $concept;
  }

  public function findForStudyAreaOrderByNameQb(
    StudyArea $studyArea,
    bool $conceptsOnly = false,
    bool $instancesOnly = false): QueryBuilder
  {
    if ($conceptsOnly && $instancesOnly) {
      throw new InvalidArgumentException('You cannot select both only options at the same time!');
    }

    $qb = $this->createQueryBuilder('c')
      ->where('c.studyArea = :studyArea')
      ->setParameter(':studyArea', $studyArea)
      ->orderBy('c.name', 'ASC');

    if ($conceptsOnly) {
      $qb->andWhere('c.instance = false');
    }
    if ($instancesOnly) {
      $qb->andWhere('c.instance = true');
    }

    return $qb;
  }

  /** @return Concept[] */
  public function findForStudyAreaOrderedByName(
    StudyArea $studyArea,
    bool $preLoadData = false,
    bool $conceptsOnly = false,
    bool $instancesOnly = false)
  {
    $qb = $this->findForStudyAreaOrderByNameQb($studyArea, $conceptsOnly, $instancesOnly);

    $this->loadRelations($qb, 'c');

    if ($preLoadData) {
      $this->preLoadData($qb, 'c');
    }

    return $qb->getQuery()->getResult();
  }

  /**
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getCountForStudyArea(
    StudyArea $studyArea,
    bool $conceptsOnly = false,
    bool $instancesOnly = false): int
  {
    if ($conceptsOnly && $instancesOnly) {
      throw new InvalidArgumentException('You cannot select both only options at the same time!');
    }

    $qb = $this->createQueryBuilder('c')
      ->select('COUNT(c.id)')
      ->where('c.studyArea = :studyArea')
      ->setParameter('studyArea', $studyArea);

    if ($conceptsOnly) {
      $qb->andWhere('c.instance = false');
    }
    if ($instancesOnly) {
      $qb->andWhere('c.instance = true');
    }

    return $qb->getQuery()->getSingleScalarResult();
  }

  /** Eagerly load the concept relations, while applying the soft deletable filter. */
  private function loadRelations(QueryBuilder &$qb, string $alias)
  {
    $qb
      ->leftJoin($alias . '.outgoingRelations', 'r')
      ->leftJoin($alias . '.incomingRelations', 'ir')
      ->addSelect('r')
      ->addSelect('ir');
  }

  /** Eagerly load the text data. */
  private function preLoadData(QueryBuilder &$qb, string $alias)
  {
    $qb
      ->join($alias . '.examples', 'de')
      ->join($alias . '.introduction', 'di')
      ->join($alias . '.theoryExplanation', 'dt')
      ->join($alias . '.howTo', 'dh')
      ->join($alias . '.selfAssessment', 'ds')
      ->join($alias . '.additionalResources', 'da')
      ->addSelect('de')
      ->addSelect('di')
      ->addSelect('dt')
      ->addSelect('dh')
      ->addSelect('ds')
      ->addSelect('da');
  }
}
