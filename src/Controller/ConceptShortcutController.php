<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConceptShortcutController extends AbstractController
{

  #[Route('/{concept<(?i:(\w{2,3}-\d{2}-\d{2,3}$))>}', name: "app_concept_shortcut", methods: 'GET')]
  public function gistBokTopics(string $concept): Response
  {
    return $this->redirectToRoute('_home_simple', ['pageUrl' => 'concept/'. $concept]);
  }

}
