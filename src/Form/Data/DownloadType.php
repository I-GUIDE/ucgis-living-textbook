<?php

namespace App\Form\Data;

use App\Entity\StudyArea;
use App\Export\ExportService;
use App\Form\Type\SingleSubmitType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadType extends AbstractType
{
  public function __construct(private readonly ExportService $exportService)
  {
  }

  #[Override]
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('type', ChoiceType::class, [
        'label'   => 'data.download.type',
        'choices' => $this->exportService->getChoices(),
        'attr'    => [
          'class' => 'download-type',
        ],
      ])
      ->add('preview', DownloadPreviewType::class)
      ->add('submit', SingleSubmitType::class, [
        'label' => 'data.download.title',
        'icon'  => 'fa-download',
        'attr'  => [
          'class' => 'btn btn-outline-success',
        ],
      ]);
  }

  #[Override]
  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver
      ->setRequired('current_study_area')
      ->setAllowedTypes('current_study_area', StudyArea::class)
      ->setDefaults([
        'attr' => [
          'target' => '_blank',
        ],
      ]);

    $resolver->setNormalizer('attr', function (OptionsResolver $optionsResolver, $attr) {
      /** @var StudyArea $studyArea */
      $studyArea = $optionsResolver->offsetGet('current_study_area');
      if ($studyArea->isUrlExportEnabled() && $studyArea->getExportUrl() !== null) {
        $attr['target'] = '_self';
      } else {
        $attr['target'] = '_blank';
      }
      return $attr;
    });
  }
}
