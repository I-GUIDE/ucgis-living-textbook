<?php


namespace App\Entity\Contracts;


interface ISearchable
{
  /**
   * Searches in the object on the given search, returns an array with search result metadata
   * '_data'   => the object,
   * '_title'  => object title,
   * 'results' => ['prio' => result priority, 'property' => object property for result, 'data' => result data]
   *
   * @param string $search
   *
   * @return array
   */
  public function searchIn(string $search): array;
}
