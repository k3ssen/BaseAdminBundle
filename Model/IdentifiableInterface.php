<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

interface IdentifiableInterface
{
    public static function getIdName();
}