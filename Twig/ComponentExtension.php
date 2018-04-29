<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

class ComponentExtension extends \Twig_Extension
{
    protected static $optionsEnvAndSafe = [
        'needs_environment' => true,
        'is_safe' => ['html']
    ];

    public function getTokenParsers()
    {
        return [
            new ComponentParser('box_body'),
        ];
    }

    public function getFunctions()
    {
        return [
//            new \Twig_SimpleFunction('box', [$this, 'renderBox'], static::$optionsEnvAndSafe),
            new \Twig_SimpleFunction('button', [$this, 'renderButton'], static::$optionsEnvAndSafe),
            new \Twig_SimpleFunction('icon', [$this, 'renderIcon'], static::$optionsEnvAndSafe),
        ];
    }

    public function getFilters()
    {
        return [
//            new \Twig_SimpleFilter('box', [$this, 'renderBox'], static::$optionsEnvAndSafe),
        ];
    }

    public function renderButton(
        \Twig_Environment $environment,
        string $name,
        ?string $type = null
    ) {
        return $environment->render('layout/components/button.html.twig', [
            'name' => $name,
            'type' => $type,
        ]);
    }

    public function renderBox(
        \Twig_Environment $environment,
        string $content = null,
        string $title = null,
        $entity = null
    ) {
        return $environment->render('layout/components/box.html.twig', [
            'content' => $content,
            'title' => $title,
            'entity' => $entity
        ]);
    }

    public function renderIcon(
        \Twig_Environment $environment,
        string $iconName,
        $type = null
    ) {
        return $environment->render('layout/components/icon.html.twig', [
            'icon_name' => $iconName,
            'type' => $type,
        ]);
    }
}
