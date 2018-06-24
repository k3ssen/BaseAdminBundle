<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

class BaseAdminExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        foreach ($container->getExtensionConfig('security') as $securityConfig) {
            $strategy = $securityConfig['access_decision_manager']['strategy'] ?? null;
            if ($strategy) {
                break;
            }
        }
        if (!isset($strategy)) {
            $strategy = AccessDecisionManager::STRATEGY_AFFIRMATIVE;
        }

        $container->setParameter('base_admin.voter_strategy', $strategy);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        foreach ($config as $key => $value) {
            $container->setParameter('base_admin.'.$key, $value);
        }
    }
}
