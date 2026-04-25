<?php

namespace App\ConceptPrint;

use App\Entity\Concept;

interface ConceptPdfGeneratorInterface
{

  public function create(Concept $concept): string;

}
