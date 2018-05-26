<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\EntityTraits;

use Symfony\Component\Security\Core\User\UserInterface;

interface BlameableInterface
{
    public function setCreatedBy(UserInterface $user);
    public function getCreatedBy(): ?UserInterface;
    public function setUpdatedBy(UserInterface $user);
    public function getUpdatedBy(): ?UserInterface;
}
