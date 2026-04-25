<?php

namespace App\ConceptPrint;

use Bobv\LatexBundle\Generator\LatexGeneratorInterface;
use Bobv\LatexBundle\Latex\Base\Standalone;
use Bobv\LatexBundle\Latex\Element\CustomCommand;
use Drenso\PdfToImage\Pdf;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function md5;
use function str_replace;

readonly class LatexEquationGenerator
{

  public function __construct(
    private Security $security,
    private RequestStack $requestStack,
    private CacheInterface $cache,
    #[Target('latex_generator.limiter')] private RateLimiterFactory $latexGeneratorLimiter,
    private LatexGeneratorInterface $generator,
    #[Autowire('%bobv.latex.error_image%')] private string $errorImageSrc,
  )
  {
  }

  /**
   * Generate a latex equation and return the location of the image.
   *
   * @param string $latex
   *
   * @return string
   * @throws InvalidArgumentException
   * @throws LatexEquationException
   */
  public function generate(string $latex): string
  {
    $key = md5($latex);

    return $this->cache->get($key, function (ItemInterface $item) use ($latex) {
      $item->expiresAfter(86400);
      try {
        if (!$this->security->isGranted('ROLE_USER')) {
          $this->latexGeneratorLimiter->create($this->requestStack->getCurrentRequest()->getClientIp())
            ->consume()
            ->ensureAccepted();
        }
        $document      = new Standalone(md5($latex))
          ->addPackages(['mathtools', 'amssymb', 'esint'])
          ->addElement(new CustomCommand('\\begin{displaymath}'))
          ->addElement(new CustomCommand($latex))
          ->addElement(new CustomCommand('\\end{displaymath}'));
        $pdfLocation   = $this->generator->generate($document);
        $imageLocation = str_replace('.pdf', '.png', $pdfLocation);
        $pdf           = new Pdf($pdfLocation);
        $pdf->saveImage($imageLocation);

        return $imageLocation;
      } catch (RateLimitExceededException $ex) {
        $limit      = $ex->getRateLimit();
        $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
        throw new TooManyRequestsHttpException($retryAfter, headers: [
          'X-RateLimit-Remaining'   => $limit->getRemainingTokens(),
          'X-RateLimit-Retry-After' => $retryAfter,
          'X-RateLimit-Limit'       => $limit->getLimit(),
        ]);
      } catch (Exception $ex) {
        throw new LatexEquationException($latex, $this->errorImageSrc, $ex);
      }
    });
  }

}
