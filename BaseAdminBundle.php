<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle;

use K3ssen\BaseAdminBundle\DependencyInjection\Compiler\BaseAdminCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BaseAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new BaseAdminCompilerPass());
    }
}
