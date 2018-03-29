<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRoleInfoProvider
{
    /** @var AccessDecisionManagerInterface */
    protected $decisionManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(AccessDecisionManagerInterface $decisionManager, TokenStorageInterface $tokenStorage)
    {
        $this->decisionManager = $decisionManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function isCreator($object)
    {
        //TODO: use BlameableInterface
        return $object->createdBy() === $this->getUser();
    }

    public function isOwner($object)
    {
        //TODO: use OwnableInterface
        return $object->createdBy() === $this->getUser();
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function isAdmin()
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isUser()
    {
        return $this->hasRole('ROLE_USER');
    }

    public function hasRole(string $role): bool
    {
        return $this->decisionManager->decide($this->getToken(), [$role]);
    }

    public function getUser()
    {
        return $this->getToken()->getUser();
    }

    protected function isLoggedIn()
    {
        return $this->getUser() instanceof UserInterface;
    }

    protected function getToken(): TokenInterface
    {
        return $this->tokenStorage->getToken();
    }
}
