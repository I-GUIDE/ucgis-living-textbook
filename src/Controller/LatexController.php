<?php

namespace App\Controller;

use App\ConceptPrint\LatexEquationException;
use App\ConceptPrint\LatexEquationGenerator;
use DateTime;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function filemtime;

#[Route('/latex')]
class LatexController extends AbstractController
{
  /** @throws InvalidArgumentException */
  #[Route('/render', options: ['expose' => true, 'no_login_wrap' => true], methods: [Request::METHOD_GET])]
  #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
  public function renderLatex(
    Request $request,
    LatexEquationGenerator $generator,
  ): Response
  {
    $content = $request->query->get('content', null);
    if (!$content) {
      throw $this->createNotFoundException();
    }
    try {
      $imageLocation = $generator->generate($content);
      $response      = $this->file($imageLocation, null, ResponseHeaderBag::DISPOSITION_INLINE);
      $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
      $response->setLastModified(DateTime::createFromFormat('U', (string)filemtime($imageLocation)));
      $response->setAutoEtag();
      $response->setMaxAge(604800); // One week
      $response->setPrivate();
      $response->isNotModified($request);
    } catch (LatexEquationException $ex) {
      $response = $this->file($ex->getErrorImageSrc(), null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    return $response;
  }
}
