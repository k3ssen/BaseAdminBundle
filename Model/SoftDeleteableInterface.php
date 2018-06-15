<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

/**
 * Alternative to Gedmo\SoftDeleteable\SoftDeleteable, since that interface does not promise any method.
 * This interface can be implemented by using SoftDeleteableEntityTrait
 */
interface SoftDeleteableInterface
{
    /**
     * @return static
     */
    public function setDeletedAt(\DateTimeImmutable $deletedAt = null);

    public function getDeletedAt(): ?\DateTimeImmutable;

    public function isDeleted(): bool ;
}