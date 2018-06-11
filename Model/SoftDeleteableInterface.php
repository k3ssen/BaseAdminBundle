<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

/**
 * Alternative to Gedmo\SoftDeleteable\SoftDeleteable, since that interface does not enforce any method.
 * This interface is compatible with trait: Gedmo\SoftDeleteable\Traits\SoftDeleteable
 */
interface SoftDeleteableInterface
{
    /**
     * Sets deletedAt.
     *
     * @param \DateTime|null $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt = null);

    /**
     * Returns deletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt();

    /**
     * Is deleted?
     *
     * @return bool
     */
    public function isDeleted();
}