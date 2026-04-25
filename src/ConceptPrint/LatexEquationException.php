<?php

namespace App\ConceptPrint;

use RuntimeException;
use Throwable;

class LatexEquationException extends RuntimeException
{

  public function __construct(string $latex, private readonly string $errorImageSrc, Throwable $cause) {
    parent::__construct("Error generating LaTeX equation: $latex", E_ERROR, $cause);
  }

  public function getErrorImageSrc(): string {
    return $this->errorImageSrc;
  }

}
