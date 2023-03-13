<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_route_active', [$this, 'isRouteActive']),
        ];
    }

    public function isRouteActive($routes, string $currentRoute)
    {
        if(is_array($routes)){
            foreach($routes as $route){
                if($route === $currentRoute){
                    return 'active';
                }
            }
        }else{
            if($routes === $currentRoute){
                return 'active';
            }
        }

        return '';
    }
}
