<?php

namespace App\ConceptPrint;

use App\Entity\Concept;
use Mpdf\Mpdf;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;

readonly class MpdfConceptGenerator implements ConceptPdfGeneratorInterface
{

  public function __construct(
    private CacheInterface $cache,
    private Environment $twig,
    #[Autowire('%kernel.project_dir%')] private string $projectDir,
    #[Autowire('%kernel.cache_dir%')] private string $cacheDir,
  )
  {
  }

  /**
   * @throws InvalidArgumentException
   */
  public function create(Concept $concept): string
  {
    $key         = 'concept_pdf_' . $concept->getId();
    return $this->cache->get($key, function (ItemInterface $item) use ($concept) {
      $item->expiresAfter(7776000);
      $projectDir = $this->projectDir . '/public';
      $pdf        = new Mpdf([
        'anchor2Bookmark' => true,
        'margin_top'      => 15,
        'margin_bottom'   => 25,
        'margin_header'   => 5,
        'margin_footer'   => 10,
        'tempDir'         => $this->cacheDir
      ]);
      $pdf->SetSubject($concept->getName());
      $keywords = implode(', ', $concept->getTags()->map(static fn ($tag) => $tag->getName())->toArray());
      $pdf->SetKeywords($keywords);
      $pdf->WriteHTML($this->twig->render('concept_print/concept.html.twig', [
        'keywords'   => $keywords,
        'concept'    => $concept,
        'projectDir' => $projectDir
      ]));
      $outfile = $concept->getSlug() . '.pdf';
      return $pdf->Output($outfile, 'S');
    });
  }
}
