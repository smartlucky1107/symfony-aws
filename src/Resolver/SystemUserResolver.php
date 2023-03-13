<?php

namespace App\Resolver;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SystemUserResolver
{
    /** @var ParameterBagInterface */
    private $parameters;

    /**
     * SystemUserResolver constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }
}
