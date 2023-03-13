<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController
{
    private string $baseUrl;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->baseUrl = $parameterBag->get('frontend_base_url');
    }

    /**
     * @Route("/", name="index")
     */
    public function index(): RedirectResponse
    {
        return new RedirectResponse($this->baseUrl);
    }
}
