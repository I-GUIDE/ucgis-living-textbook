<?php

namespace App\ConceptPrint;

use App\Entity\Concept;
use DateTimeInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use RuntimeException;


/**
 * This class handles rate-limited generation of concept PDFs,
 * ensuring that certain users or requests adhere to a predefined consumption limit.
 * It utilizes a caching mechanism to avoid redundant processing and rate limit checking for repeated requests.
 */
readonly class RateLimitedConceptPrinter
{

  /**
   * @param CacheItemPoolInterface       $conceptPrintCache
   * @param RequestStack                 $requestStack
   * @param RateLimiterFactory           $rateLimiter
   * @param ConceptPdfGeneratorInterface $conceptPdfGenerator
   * @param Security                     $security
   */
  public function __construct(
    private CacheItemPoolInterface $conceptPrintCache,
    private RequestStack $requestStack,
    #[Target('pdf_print.limiter')] private RateLimiterFactory $rateLimiter,
    private ConceptPdfGeneratorInterface $conceptPdfGenerator,
    private Security $security,
  )
  {

  }

  private function cacheKeyFor(Concept $concept): string
  {
    return 'concept_pdf_' . $concept->getId();
  }


  /**
   * Generate a PDF for the given Concept object, applying rate limiting and caching.
   *
   * @throws RuntimeException
   * @throws TooManyRequestsHttpException
   */
  function print(Concept $concept): string
  {
    try {
      $key = $this->cacheKeyFor($concept);
      $item = $this->conceptPrintCache->getItem($key);
      if (!$item->isHit()) {
        if (!$this->security->getUser()) {
          $limiter = $this->rateLimiter->create($this->requestStack->getCurrentRequest()->getClientIp());
          $limit   = $limiter->consume();
          if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->format(DateTimeInterface::RFC7231));
          }
        }

        $item->set($this->conceptPdfGenerator->create($concept));
        $this->conceptPrintCache->save($item);

      }
      return $item->get();
    } catch (InvalidArgumentException $e) {
      throw new RuntimeException('Cache is not configured properly', 0, $e);
    }
  }

  public function clear(Concept $concept): bool
  {
    return $this->conceptPrintCache->deleteItem($this->cacheKeyFor($concept));
  }

  public function has(Concept $concept): bool
  {
    return $this->conceptPrintCache->hasItem($this->cacheKeyFor($concept));
  }

}
