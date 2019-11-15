<?php

namespace App\Analytics\Exception;

use Exception;
use Throwable;

class VisualisationDependenciesFailed extends Exception
{
  public function __construct(string $dependency, Throwable $previous = NULL)
  {
    parent::__construct(sprintf('Dependency %s failed to build', $dependency), 0, $previous);
  }
}