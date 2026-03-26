<?php

namespace App\Controller;

use App\ConceptPrint\Base\ConceptPrint;
use App\ConceptPrint\ConceptPdfGeneratorInterface;
use App\ConceptPrint\ImageResolver;
use App\ConceptPrint\Section\ConceptSection;
use App\ConceptPrint\Section\LearningPathSection;
use App\Entity\Concept;
use App\Entity\LearningPath;
use App\Entity\StudyArea;
use App\Naming\NamingService;
use App\Request\Wrapper\RequestStudyArea;
use App\Router\LtbRouter;
use App\Security\Voters\StudyAreaVoter;
use Bobv\LatexBundle\Exception\ImageNotFoundException;
use Bobv\LatexBundle\Exception\LatexException;
use Bobv\LatexBundle\Generator\LatexGeneratorInterface;
use Bobv\LatexBundle\Helper\Sanitize;
use Exception;
use Mpdf\Mpdf;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function implode;

#[Route('/{_studyArea<\d+|(?i:%study_area_slug%)>}/print')]
class PrintController extends AbstractController
{
  /** @throws Exception
   * @throws InvalidArgumentException
   */
  #[Route('/concept/{concept<\d+|(?i:(\w*)(-\w+)*)>}')]
  #[IsGranted(StudyAreaVoter::PRINTER, subject: 'requestStudyArea')]
  public function printSingleConcept(
    RequestStudyArea $requestStudyArea,
    ConceptPdfGeneratorInterface $generator,
    #[MapEntity(expr: 'repository.findOneByIdOrSlug(_studyArea, concept)')] Concept $concept)
  {
//    $key         = 'concept_pdf_' . $concept->getId();
//    $pdf = $cache->get($key, function (ItemInterface $item) use ($concept) {
//      $item->expiresAfter(7776000);
//      $projectDir = $this->getParameter('kernel.project_dir') . '/public';
//      $pdf        = new Mpdf([
//        'anchor2Bookmark' => true,
//        'margin_top'      => 15,
//        'margin_bottom'   => 25,
//        'margin_header'   => 5,
//        'margin_footer'   => 10,
//        'tempDir'         => $this->getParameter('kernel.project_dir') . '/var/cache'
//      ]);
//      $pdf->SetSubject($concept->getName());
//      $keywords = implode(', ', $concept->getTags()->map(static fn ($tag) => $tag->getName())->toArray());
//      $pdf->SetKeywords($keywords);
//      $pdf->WriteHTML($this->renderView('concept_print/concept.html.twig', [
//        'keywords'   => $keywords,
//        'concept'    => $concept,
//        'projectDir' => $projectDir
//      ]));
//      $outfile = $concept->getSlug() . '.pdf';
//      return $pdf->Output($outfile, 'S');
//    });

    return new Response(
      $generator->create($concept),
      200,
      [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $concept->getSlug() . '.pdf"',
        'Cache-Control'       => 'public, max-age=86400, s-maxage=86400',
        'Expires'             => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
      ]
    );

//    $projectDir = $this->getParameter('kernel.project_dir') .'/public';
//
//    $pdf = new Mpdf([
//      'anchor2Bookmark' => true,
//      'margin_top' => 15,
//      'margin_bottom' => 25,
//      'margin_header' => 5,
//      'margin_footer' => 10,
//      'tempDir' => $this->getParameter('kernel.project_dir') . '/var/cache'
//    ]);
//    $pdf->SetSubject($concept->getName());
//    $keywords = implode(', ', $concept->getTags()->map(static fn ($tag) => $tag->getName())->toArray());
//    $pdf->SetKeywords($keywords);
//    $pdf->curlAllowUnsafeSslRequests = true;
//    $pdf->WriteHTML($this->renderView('concept_print/concept.html.twig', [
//      'keywords' => $keywords,
//      'concept' => $concept,
//      'projectDir' => $projectDir
//    ]));

//    $outfile = $concept->getSlug() . '.pdf';
//    return new StreamedResponse(
//      static fn () => $pdf->Output($outfile, 'I'),
//      200,
//      [
//        'Content-Type'        => 'application/pdf',
//        'Content-Disposition' => 'attachment; filename="' . $outfile . '"',
//      ]);
  }

  /** @throws Exception */
  #[Route('/learningpath/{learningPath<\d+>}')]
  #[IsGranted(StudyAreaVoter::PRINTER, subject: 'requestStudyArea')]
  public function printLearningPath(
    RequestStudyArea $requestStudyArea,
    LearningPath $learningPath,
    LatexGeneratorInterface $generator,
    TranslatorInterface $translator,
    LtbRouter $router,
    NamingService $namingService): JsonResponse
  {
    return new JsonResponse(['message' => 'Not implemented yet']);
    /*// Check if correct study area
    if ($learningPath->getStudyArea()->getId() != $requestStudyArea->getStudyArea()->getId()) {
      throw $this->createNotFoundException();
    }

    $projectDir = $this->getParameter('kernel.project_dir');

    // Create LaTeX document
    $document = new ConceptPrint($this->filename($learningPath->getName()))
      ->useLicenseImage($projectDir)
      ->setBaseUrl($this->generateUrl('base_url', [], UrlGeneratorInterface::ABSOLUTE_URL))
      ->setHeader($learningPath->getStudyArea(), $translator)
      ->addIntroduction($learningPath->getStudyArea(), $translator)
      ->addElement(new LearningPathSection($learningPath, $router, $translator, $namingService, $projectDir, $downloader));

    // Return PDF
    try {
      return $generator->createPdfResponse($document, false);
    } catch (Exception $e) {
      return $this->parsePrintException($e, null, $learningPath);
    }*/
  }

  private function filename(string $name): array|false|string|null
  {
    return str_replace(' ', '-', mb_strtolower(Sanitize::sanitizeText($name)));
  }

  /**
   * Tries to parse the thrown exception into something that might be usable for the user.
   *
   * @throws Exception
   */
  private function parsePrintException(Exception $e,
    ?Concept $concept = null,
    ?LearningPath $learningPath = null): Response
  {
    // Retrieve study area from one of the given objects
    $studyArea = $concept?->getStudyArea();
    $studyArea ??= $learningPath?->getStudyArea();

    switch (true) {
      case $e instanceof ImageNotFoundException:
        assert($e instanceof ImageNotFoundException);
        $imageLocation = $e->getImageLocation();
        $isUrl         = $this->isUrl($imageLocation);

        return $this->render('print/image_not_found.html.twig', [
          'isUrl'        => $isUrl,
          'path'         => $isUrl ? $imageLocation : null,
          'basename'     => $isUrl ? null : $this->getProjectPath($imageLocation, $studyArea),
          'concept'      => $concept,
          'learningPath' => $learningPath,
          'studyArea'    => $studyArea,
        ]);
      case $e instanceof LatexException:
        assert($e instanceof LatexException);

        return $this->render('print/latex_error.html.twig', [
          'error' => $e,
        ]);
      default:
        throw $e;
    }
  }

  private function isUrl(string $url): bool
  {
    return 1 === preg_match('@^https?://@', $url);
  }

  private function getProjectPath(string $url, StudyArea $studyArea)
  {
    // Try to match against expected project path
    $matches = [];
    $regex   = sprintf('@^%s/uploads/studyarea/%d/(.+)@', $this->getParameter('kernel.project_dir'), $studyArea->getId());
    if (1 === preg_match($regex, $url, $matches)) {
      return $matches[1];
    }

    // Just return the base url
    return basename($url);
  }
}
