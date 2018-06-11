<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\DependencyInjection\Compiler;

use K3ssen\BaseAdminBundle\Security\VoterWithStrategyInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BaseAdminCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $voterStrategy = $container->getParameter('base_admin.voter_strategy');
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->getClass() && class_exists($definition->getClass(), false)) {
                if (is_subclass_of($definition->getClass(), VoterWithStrategyInterface::class, true)) {
                    $definition->addMethodCall('setStrategy', [$voterStrategy]);
                }
            }
        }
    }
}