<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Router;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class BaseAdminRouter implements RouterInterface
{
    /** @var Router */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public static function create(Router $router) {
        return new BaseAdminRouter($router);
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (is_object($parameters) && method_exists($parameters, 'getId')) {
            $parameters = [
                'id' => $parameters->getId(),
            ];
        }
        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    public function getContext()
    {
        return $this->router->getContext();
    }

    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }
}