<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This BlameableEntityTrait can be used with the BlameableEntityInterface.
 * Note that this trait refers to "App\Entity\User", which is the most common place for the User-class to reside.
 * If your User-class is located elsewhere, make sure you use something else (e.g. copy this trait and use a different targetEntity)
 */
trait BlameableEntityTrait
{
    /**
     * @var UserInterface
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var UserInterface
     *
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    protected $updatedBy;

    /**
     * Sets the User that created the record.
     *
     * @param  UserInterface $user
     * @return $this
     */
    public function setCreatedBy(?UserInterface $user)
    {
        $this->createdBy = $user;

        return $this;
    }

    /**
     * Returns the User that created the record.
     *
     * @return UserInterface
     */
    public function getCreatedBy(): ?UserInterface
    {
        return $this->createdBy;
    }

    /**
     * Sets the User that updated the record.
     *
     * @param  UserInterface $user
     * @return $this
     */
    public function setUpdatedBy(?UserInterface $user)
    {
        $this->updatedBy = $user;

        return $this;
    }

    /**
     * Returns the User that updated the record.
     *
     * @return UserInterface
     */
    public function getUpdatedBy(): ?UserInterface
    {
        return $this->updatedBy;
    }
}
