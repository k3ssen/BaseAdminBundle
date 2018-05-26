<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

class EntityExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('class_name', [$this, 'getClassShortName']),
        ];
    }

    public function getClassShortName($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }
}
