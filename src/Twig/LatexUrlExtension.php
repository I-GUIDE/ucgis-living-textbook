<?php

namespace App\Twig;

use App\ConceptPrint\LatexEquationException;
use App\ConceptPrint\LatexEquationGenerator;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LatexUrlExtension extends AbstractExtension
{
  public function __construct(private LatexEquationGenerator $generator)
  {
  }

  #[Override]
  public function getFunctions(): array
  {
    return [
      new TwigFunction('fixLatexUrls', $this->fixLatexUrls(...), ['is_safe' => ['html']]),
    ];
  }

  public function fixLatexUrls(string $html): string
  {
    $pattern = '/<img[^>]+src="\/latex\/render\?content=([^">]+)"[^>]*>/i';

    return preg_replace_callback($pattern, function ($matches) {
      // $matches[1] is the URL-encoded LaTeX string
      $encodedLatex = $matches[1];
      $latex        = urldecode($encodedLatex);
      try {
        $localPath = $this->generator->generate($latex);
      } catch (LatexEquationException $e) {
        $localPath = $e->getErrorImageSrc();
      }

      return sprintf('<img src="%s" alt="%s">', $localPath, $latex);
    }, $html);
  }

}
