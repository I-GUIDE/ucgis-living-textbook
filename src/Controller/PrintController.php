<?php

namespace App\Controller;

use App\ConceptPrint\RateLimitedConceptPrinter;
use App\Entity\Concept;
use App\Request\Wrapper\RequestStudyArea;
use App\Security\Voters\StudyAreaVoter;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/{_studyArea<\d+|(?i:%study_area_slug%)>}/print/concept')]
class PrintController extends AbstractController
{
  /** @throws Exception
   */
  #[Route('/{concept<\d+|(?i:(\w*)(-\w+)*)>}')]
  #[IsGranted(StudyAreaVoter::PRINTER, subject: 'requestStudyArea')]
  public function printConcept(
    RequestStudyArea $requestStudyArea,
    RateLimitedConceptPrinter $generator,
    #[MapEntity(expr: 'repository.findOneByIdOrSlug(_studyArea, concept)')] Concept $concept)
  {
    return new Response(
      $generator->print($concept),
      200,
      [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $concept->getSlug() . '.pdf"',
        'Cache-Control'       => 'public, max-age=86400, s-maxage=86400',
        'Expires'             => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
      ],
    );
  }

  #[Route('/{concept<\d+|(?i:(\w*)(-\w+)*)>}/clear-cached', options: ['expose' => true])]
  #[IsGranted(StudyAreaVoter::EDIT, subject: 'requestStudyArea')]
  public function clearCached(
    RequestStudyArea $requestStudyArea,
    RateLimitedConceptPrinter $generator,
    #[MapEntity(expr: 'repository.findOneByIdOrSlug(_studyArea, concept)')] Concept $concept): Response
    {
      return $this->json(['cleared' => $generator->clear($concept)]);
    }

}
