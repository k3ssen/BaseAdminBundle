<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

class ComponentExtension extends \Twig_Extension
{
    protected static $optionsEnvAndSafe = [
        'needs_environment' => true,
        'needs_context' => true,
        'is_safe' => ['html']
    ];

    public function getTokenParsers()
    {
        return [
            new SetComponentParser($this->getFunctions())
        ];
    }

    public function getFunctions()
    {
        //Note that functions without key (string) won't be be used by the SetComponentParser
        return [
            '@BaseAdmin/layout/components/box.html.twig' => new \Twig_SimpleFunction(
                'box',
                [$this, 'box'],
                static::$optionsEnvAndSafe
            ),
            '@BaseAdmin/layout/components/entity_box.html.twig' => new \Twig_SimpleFunction(
                'entity_box',
                [$this, 'entityBox'],
                static::$optionsEnvAndSafe
            ),
            '@BaseAdmin/layout/components/entity_form_box.html.twig' => new \Twig_SimpleFunction(
                'entity_form_box',
                [$this, 'entityFormBox'],
                static::$optionsEnvAndSafe
            ),
        ];
    }

    public function box(\Twig_Environment $environment, array $context = [], string $title = null)
    {
        return $environment->render('@BaseAdmin/layout/components/box.html.twig', array_merge($context, [
            'title' => $title,
        ]));
    }

    public function entityBox(\Twig_Environment $environment, array $context = [], $title = null, $entity = null, $vote = null)
    {
        return $environment->render('@BaseAdmin/layout/components/entity_box.html.twig', array_merge($context, [
            'title' => $title ?? $context['title'] ?? null,
            'entity' => $entity ?? $context['entity'] ?? null,
            'vote' => $vote ?? $context['vote'] ?? false,
        ]));
    }

    public function entityFormBox(\Twig_Environment $environment, array $context = [], $title = null, $entity = null, $useVoters = null, $form = null)
    {
        return $environment->render('@BaseAdmin/layout/components/entity_form_box.html.twig', array_merge($context, [
            'title' => $title ?? $context['title'] ?? null,
            'entity' => $entity ?? $context['entity'] ?? null,
            'form' => $form ?? $context['form'] ?? null,
            'vote' => $useVoters ?? $context['vote'] ?? false,
        ]));
    }
}
