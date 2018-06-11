<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

class ComponentExtension extends \Twig_Extension
{
    /**
     * @var ComponentsAsMethods
     */
    protected $componentsAsMethods;

    public function __construct(ComponentsAsMethods $componentsAsMethods)
    {
        $this->componentsAsMethods = $componentsAsMethods;
    }

    public function getTokenParsers()
    {
        $components = $this->getComponents();
        return [
            new IncludeComponentParser($components),
            new EmbedComponentParser($components),
        ];
    }

    public function getFunctions()
    {
        $functions = [];
        foreach ($this->getComponents() as $component) {
            // Note: setting functions dynamically won't have auto-completion :(
            $functions[] = $component->getTwigSimpleFunction();
        }
        return $functions;
    }

    /**
     * @return array|Component[]
     */
    protected function getComponents()
    {
        if (isset($this->components)) {
            return $this->components;
        }
        $this->components = [];
        // Rather than defining the component-objects directly, the ComponentByMethodDefinition is used so that we can
        // use this object (in template files) for references/auto-completion.
        $componentInfoReflection = new \ReflectionClass($this->componentsAsMethods);
        foreach ($componentInfoReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }
            $name = $method->getName();
            $argumentNames = [];
            foreach ($method->getParameters() as $param) {
                if (!$param->isOptional()) {
                    throw new \RuntimeException(sprintf('All parameters inside public methods in %s must be optional, but parameter %s is required', ComponentsAsMethods::class, $param->getName()));
                }
                $argumentNames[] = $param->getName();
            }
            $templateFile = $method->invoke($this->componentsAsMethods);
            $this->components[] = new Component($name, $templateFile, $argumentNames);
        }
        return $this->components;
    }
}
