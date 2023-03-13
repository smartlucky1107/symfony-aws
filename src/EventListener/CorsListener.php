<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST  => array('onKernelRequest', 9999),
            KernelEvents::RESPONSE => array('onKernelResponse', 9999),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $method  = $request->getRealMethod();

        // perform preflight checks
        if ('OPTIONS' === $method) {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, auth-employee, auth-employee-pin, auth-workspace-pin');
            $response->headers->set('Access-Control-Max-Age', 3601);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $event->setResponse($response);
            return;
        }

    }
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, auth-employee, auth-employee-pin, auth-workspace-pin');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $request = $event->getRequest();

//        $corsOrigin = 'POST' === $request->getMethod() ? $request->headers->get('origin') : '*';
//        $response->headers->set('Access-Control-Allow-Origin', $corsOrigin);

//        $response->headers->set('Access-Control-Allow-Origin', '*');

// USE THIS FROM FOS REST AllowedMethodsListener if would like to specify allowed methods
//        $allowedMethods = $this->loader->getAllowedMethods();
//        if (isset($allowedMethods[$event->getRequest()->get('_route')])) {
//            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods[$event->getRequest()->get('_route')]));
//        }

        if($response->getStatusCode() === 400 || $response->getStatusCode() === 401 || $response->getStatusCode() === 403 || $response->getStatusCode() === 500){
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Vary', 'Origin');
        $event->setResponse($response);

        return;
    }
}
