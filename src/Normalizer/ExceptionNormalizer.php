<?php

namespace App\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionNormalizer implements NormalizerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * ExceptionNormalizer constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (isset($context['template_data']['exception'])) {
            /** @var FlattenException $flattenException */
            $flattenException =  $context['template_data']['exception'];

            $message = $this->translator->trans($flattenException->getMessage());

            return ['message' => $message];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \Exception;
    }
}