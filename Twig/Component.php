<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Twig;

class Component
{
    public const DEFAULT_MAIN_BLOCK = 'main';
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $twigTemplatePath;
    /**
     * @var string
     */
    protected $mainBlock;
    /**
     * @var array
     */
    protected $argumentNames = [];

    public function __construct(string $name, string $twigTemplatePath, array $argumentNames = [], string $mainBlock = self::DEFAULT_MAIN_BLOCK)
    {
        $this->name = $name;
        $this->twigTemplatePath = $twigTemplatePath;
        $this->argumentNames = $argumentNames;
        $this->mainBlock = $mainBlock;
    }

    public function getTwigSimpleFunction(): \Twig_SimpleFunction
    {
        return new \Twig_SimpleFunction(
            $this->name,
            [$this, 'render'],
            ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['html']]
        );
    }

    public function render(\Twig_Environment $environment, array $context, ...$args): string
    {
        foreach ($this->argumentNames as $index => $argumentName) {
            $context[$argumentName] = $args[$index] ?? $context[$argumentName] ?? null;
        }
        return $environment->render($this->twigTemplatePath, $context);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTwigTemplatePath(): string
    {
        return $this->twigTemplatePath;
    }

    public function getMainBlock(): string
    {
        return $this->mainBlock;
    }

    public function getArgumentNames(): array
    {
        return $this->argumentNames;
    }
}