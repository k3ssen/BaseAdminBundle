<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntityExtension extends \Twig_Extension
{
    /** @var RouterInterface */
    protected $router;
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('class_short_name', [$this, 'getClassShortName']),
            new \Twig_SimpleFilter('vote', [$this, 'vote']),
            new \Twig_SimpleFilter('guess_path', [$this, 'guessPath']),
        ];
    }

    public function getClassShortName($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    public function vote($object, $suffix): bool
    {
        $voterName = $this->guessVoterName($object, $suffix);
        return $this->authorizationChecker->isGranted($voterName, $object);
    }

    public function guessPath($objectOrClassName, $suffix)
    {
        $params = [];
        $className = is_string($objectOrClassName) ? $objectOrClassName : get_class($objectOrClassName);
        $routeName = $this->guessRouteName($className, $suffix);
        $route = $this->router->getRouteCollection()->get($routeName);
        if (!$route) {
            throw new \RuntimeException(sprintf('Unable to guess route for instance of "%s" and suffix "%s"', $className, $suffix));
        }
        preg_match('/[^{]*\{([^}]*)\}.*/', str_replace('{entityName}', '', $route->getPath()), $matches);
        $paramName = $matches[1] ?? null;

        $getter = null;
        if ($paramName) {
            $getter = 'get' . ucfirst($paramName);
            if (!method_exists($objectOrClassName, $getter)) {
                throw new \RuntimeException(sprintf(
                    'Unable to guess route for instance of "%s" and suffix "%s". Route "%s" was found, but no match could be derived for param "%s"',
                    $className,
                    $suffix,
                    $routeName,
                    $paramName
                ));
            }
            $params[$paramName] =  $objectOrClassName->$getter();
        }

        return $this->router->generate($routeName, $params);
    }

    protected function guessRouteName($object, $suffix)
    {
        return $this->guessRoutePrefix($object) . '_' . $suffix;
    }

    protected function guessVoterName($object, $suffix)
    {
        return strtoupper($this->guessRoutePrefix($object) . '_' . $suffix);
    }

    protected function guessRoutePrefix($objectOrClassName)
    {
        $className = is_string($objectOrClassName) ? $objectOrClassName : get_class($objectOrClassName);
        $parts = explode('\\Entity\\', $className);
        $subDirAndEntityName = array_pop($parts);
        return Inflector::tableize(str_replace('\\','_', $subDirAndEntityName));
    }
}
