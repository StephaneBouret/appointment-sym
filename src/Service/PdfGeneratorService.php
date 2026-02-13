<?php

namespace App\Service;

use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Component\HttpFoundation\Response;

class PdfGeneratorService
{
    public function __construct(protected DompdfWrapperInterface $wrapper) {}

    public function getStreamResponse(string $html, string $filename): Response
    {
        return $this->wrapper->getStreamResponse($html, $filename);
    }

    public function getLandscapeStreamResponse(string $html, string $filename): Response
    {
        // Ajout des options pour le format paysage
        $options = [
            'defaultPaperSize' => 'a4',
            'defaultPaperOrientation' => 'landscape',
        ];

        return $this->wrapper->getStreamResponse($html, $filename, $options);
    }
}
