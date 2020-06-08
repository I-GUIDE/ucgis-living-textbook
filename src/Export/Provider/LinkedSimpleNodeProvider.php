<?php

namespace App\Export\Provider;

use App\Entity\Concept;
use App\Entity\StudyArea;
use App\Export\ExportService;
use App\Export\ProviderInterface;
use App\Naming\NamingService;
use App\Repository\ConceptRelationRepository;
use App\Repository\ConceptRepository;
use App\Repository\ContributorRepository;
use App\Repository\ExternalResourceRepository;
use App\Repository\LearningOutcomeRepository;
use App\Repository\RelationTypeRepository;
use App\Router\LtbRouter;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;

class LinkedSimpleNodeProvider implements ProviderInterface
{
  /**
   * @var ConceptRepository
   */
  private $conceptRepository;
  /**
   * @var ConceptRelationRepository
   */
  private $conceptRelationRepository;
  /**
   * @var ContributorRepository
   */
  private $contributorRepository;
  /**
   * @var ExternalResourceRepository
   */
  private $externalResourceRepository;
  /**
   * @var LearningOutcomeRepository
   */
  private $learningOutcomeRepository;
  /**
   * @var NamingService
   */
  private $namingService;
  /**
   * @var RelationTypeRepository
   */
  private $relationTypeRepository;
  /**
   * @var LtbRouter
   */
  private $router;
  /**
   * @var SerializerInterface
   */
  private $serializer;
  /**
   * @var AsciiSlugger
   */
  private $slugger;

  public function __construct(
      ConceptRepository $conceptRepository, ConceptRelationRepository $conceptRelationRepository,
      ContributorRepository $contributorRepository, ExternalResourceRepository $externalResourceRepository,
      LearningOutcomeRepository $learningOutcomeRepository, RelationTypeRepository $relationTypeRepository,
      SerializerInterface $serializer, NamingService $namingService, LtbRouter $router)
  {
    $this->conceptRepository          = $conceptRepository;
    $this->conceptRelationRepository  = $conceptRelationRepository;
    $this->contributorRepository      = $contributorRepository;
    $this->externalResourceRepository = $externalResourceRepository;
    $this->learningOutcomeRepository  = $learningOutcomeRepository;
    $this->relationTypeRepository     = $relationTypeRepository;
    $this->serializer                 = $serializer;
    $this->namingService              = $namingService;
    $this->router                     = $router;
    $this->slugger                    = new AsciiSlugger();
  }

  /**
   * @inheritdoc
   */
  public function getName(): string
  {
    return 'linked-simple-node';
  }

  /**
   * @inheritdoc
   */
  public function getPreview(): string
  {
    $fieldNames = $this->namingService->get()->concept();

    return sprintf(<<<'EOT'
{
    "nodes": [
        {
            "instance": "<concept-instance>",
            "label": "<concept-name>",
            "link": "<concept-url>",
            "numberOfLinks": <number-of-relations>,
            "%s": "<concept-%s>",
            "%s": "<concept-%s>",
        }
    ],
    "links": [
        {
            "target": <target-id>,
            "source": <source-id>,
            "relationName": "<relation-name>"
        }
    ],
    "contributors": [
        {
            "nodes": [<node-ids>],
            "name": "<contributor-name>",
            "description": "<contributor-description>",
            "url": "<contributor-url>",
            "email": "<contributor-email>"
        }
    ],
    "external_resources": [
        {
            "nodes": [<node-ids>],
            "title": "<external-resource-title>",
            "description": "<external-resource-description>",
            "url": "<external-resource-url>",
        }
    ]
    "learning_outcomes": [
        {
            "nodes": [<node-ids>],
            "number": "<learning-outcome-number>",
            "name": "<learning-outcome-name>",
            "content": "<learning-outcome-content>",
        }
    ]
}
EOT,
        $this->fieldName($fieldNames->definition()),
        $this->fieldDescription($fieldNames->definition()),
        $this->fieldName($fieldNames->selfAssessment()),
        $this->fieldDescription($fieldNames->selfAssessment()));
  }

  /**
   * @inheritdoc
   */
  public function export(StudyArea $studyArea): Response
  {
    /** @noinspection PhpUnusedLocalVariableInspection Retrieve the relation types as cache */
    $relationTypes = $this->relationTypeRepository->findBy(['studyArea' => $studyArea]);

    // Retrieve the concepts
    $concepts          = $this->conceptRepository->findForStudyAreaOrderedByName($studyArea);
    $links             = $this->conceptRelationRepository->findByConcepts($concepts);
    $contributors      = $this->contributorRepository->findForStudyArea($studyArea);
    $externalResources = $this->externalResourceRepository->findForStudyAreaOrderedByTitle($studyArea);
    $learningOutcomes  = $this->learningOutcomeRepository->findForStudyAreaOrderedByName($studyArea);

    // Detach the data from the ORM
    $idMap = [];
    foreach ($concepts as $key => $concept) {
      $idMap[$concept->getId()] = $key;
    }

    // Create link data
    $mappedLinks = [];
    foreach ($links as &$link) {
      $mappedLinks[] = [
          'target'       => $idMap[$link->getTargetId()],
          'source'       => $idMap[$link->getSourceId()],
          'relationName' => $link->getRelationName(),
      ];
    }

    // Create contributors data
    $mappedContributors = [];
    foreach ($contributors as $contributor) {
      $mappedContributors[] = [
          'nodes'       => $contributor->getConcepts()->map(function (Concept $concept) use ($idMap) {
            return $idMap[$concept->getId()];
          }),
          'name'        => $contributor->getName(),
          'description' => $contributor->getDescription(),
          'url'         => $contributor->getUrl(),
          'email'       => $contributor->getEmail(),
      ];
    }

    // Create external resource data
    $mappedExternalResources = [];
    foreach ($externalResources as $externalResource) {
      $mappedExternalResources[] = [
          'nodes'       => $externalResource->getConcepts()->map(function (Concept $concept) use ($idMap) {
            return $idMap[$concept->getId()];
          }),
          'title'       => $externalResource->getTitle(),
          'description' => $externalResource->getDescription(),
          'url'         => $externalResource->getUrl(),
      ];
    }

    // Create learning outcomes data
    $mappedLearningOutcomes = [];
    foreach ($learningOutcomes as $learningOutcome) {
      $mappedLearningOutcomes[] = [
          'nodes'   => $learningOutcome->getConcepts()->map(function (Concept $concept) use ($idMap) {
            return $idMap[$concept->getId()];
          }),
          'number'  => $learningOutcome->getNumber(),
          'name'    => $learningOutcome->getName(),
          'content' => $learningOutcome->getText(),
      ];
    }

    // Create JSON data
    {
      $fieldNames     = $this->namingService->get()->concept();
      $definition     = $this->fieldName($fieldNames->definition());
      $selfAssessment = $this->fieldName($fieldNames->selfAssessment());

      // Return as JSON
      $serializationContext = SerializationContext::create();
      $serializationContext->setSerializeNull(true);
      $json = $this->serializer->serialize(
          [
              'nodes'              => array_map(function (Concept $concept) use ($definition, $selfAssessment) {
                return [
                    'instance'      => $concept->isInstance(),
                    'label'         => $concept->getName(),
                    'link'          => $this->router->generateBrowserUrl('app_concept_show', ['concept' => $concept->getId()]),
                    'numberOfLinks' => $concept->getNumberOfLinks(),
                    $definition     => $concept->getDefinition(),
                    $selfAssessment => $concept->getSelfAssessment()->getText(),
                ];
              }, $concepts),
              'links'              => $mappedLinks,
              'contributors'       => $mappedContributors,
              'external_resources' => $mappedExternalResources,
              'learning_outcomes'  => $mappedLearningOutcomes,
          ],
          'json', $serializationContext);

      $response = new JsonResponse($json, Response::HTTP_OK, [], true);
      ExportService::contentDisposition($response, sprintf('%s_export.json', $studyArea->getName()));

      return $response;
    }
  }

  private function fieldName(string $fieldName): string
  {
    return u($fieldName)->ascii()->camel();
  }

  private function fieldDescription(string $fieldName): string
  {
    return $this->slugger->slug($fieldName)->lower();
  }
}
