<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteableEntityTrait
{
    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected $deletedAt;

    /**
     * @return static
     */
    public function setDeletedAt(\DateTimeImmutable $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }
}
