<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Repository;

trait PersistableEntityRepositoryTrait
{
    public function save($entity, $flush = true)
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function delete($entity, $flush = true)
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function flush()
    {
        $this->_em->flush();
    }
}
