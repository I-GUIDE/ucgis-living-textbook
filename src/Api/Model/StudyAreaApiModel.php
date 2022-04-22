<?php

namespace App\Api\Model;

use App\Entity\StudyArea;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type;
use OpenApi\Attributes as OA;

class StudyAreaApiModel
{
  protected function __construct(
      protected readonly int $id,
      protected readonly string $name,
      #[OA\Property(nullable: true)]
      protected readonly ?string $description,
      #[OA\Property(nullable: true)]
      protected readonly ?string $group,
      public readonly bool $dotron,
      #[OA\Property(description: 'Specific Dotron configuration for a study area, only returned when Dotron is been enabled', type: 'object', nullable: true)]
      #[Type('array')]
      #[Groups(['dotron'])]
      #[Exclude(if: 'object.dotron == false')]
      protected readonly ?array $dotronConfig,
  ) {
  }

  public static function fromEntity(StudyArea $studyArea): self
  {
    return new self(
        $studyArea->getId(),
        $studyArea->getName(),
        $studyArea->getDescription(),
        $studyArea->getGroup()?->getName(),
        $studyArea->isDotron(),
        $studyArea->getDotronConfig()
    );
  }
}