<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Use this BlameableInterface when you have a User entity that you can refer to.
 */
interface BlameableEntityInterface
{
    /**
     * @return static
     */
    public function setCreatedBy(?UserInterface $user);

    /**
     * @return static
     */
    public function getCreatedBy(): ?UserInterface;

    /**
     * @return static
     */
    public function setUpdatedBy(UserInterface $user);

    public function getUpdatedBy(): ?UserInterface;
}
