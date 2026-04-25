<?php

namespace App\ConceptPrint;

use App\Entity\Concept;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use RuntimeException;

/**
 * This class generates PDF documents for Concept objects using the Mpdf library.
 * Implements the ConceptPdfGeneratorInterface for creating PDF output from provided data.
 */
readonly class MpdfConceptGenerator implements ConceptPdfGeneratorInterface
{

  public function __construct(
    private Environment $twig,
    #[Autowire('%kernel.project_dir%')] private string $projectDir,
    #[Autowire('%kernel.cache_dir%')] private string $cacheDir,
  )
  {
  }

  /**
   * Create a PDF document for the given Concept object.
   *
   * @param Concept $concept
   *
   * @return string
   */
  public function create(Concept $concept): string
  {
    try {
      $projectDir = $this->projectDir . '/public';
      $pdf        = new Mpdf([
        'anchor2Bookmark' => true,
        'margin_top'      => 15,
        'margin_bottom'   => 25,
        'margin_header'   => 5,
        'margin_footer'   => 10,
        'tempDir'         => $this->cacheDir,
      ]);
      $pdf->SetSubject($concept->getName());
      $keywords = implode(', ', $concept->getTags()->map(static fn ($tag) => $tag->getName())->toArray());
      $pdf->SetKeywords($keywords);
      $pdf->WriteHTML($this->twig->render('concept_print/concept.html.twig', [
        'keywords'   => $keywords,
        'concept'    => $concept,
        'projectDir' => $projectDir,
      ]));
      $outfile = $concept->getSlug() . '.pdf';

      return $pdf->Output($outfile, 'S');
    } catch (MpdfException|SyntaxError|RuntimeError|LoaderError $e) {
      throw new RuntimeException($e->getMessage(), previous: $e);
    }
  }
}
