<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

use Doctrine\ORM\Mapping as ORM;

trait TimestampableEntityTrait
{
    /**
     * @var \DateTimeImmutable
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime_immutable")
     */
    protected $createdAt;

    /**
     * @var \DateTimeImmutable
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected $updatedAt;

    /**
     * @param  \DateTimeImmutable $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param  \DateTimeImmutable $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
