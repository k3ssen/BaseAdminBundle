<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Repository;

interface PersistableEntityRepositoryInterface
{
    public function save($entity, $flush = true);
    public function delete($entity, $flush = true);
    public function flush();
}
