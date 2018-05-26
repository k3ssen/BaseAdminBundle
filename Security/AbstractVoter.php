<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Security;

use K3ssen\BaseAdminBundle\EntityTraits\BlameableInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractVoter implements VoterInterface
{
    /** @var TokenInterface */
    protected $token;

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;
        $this->token = $token;

        foreach ($attributes as $attribute) {
            $granted = $this->voteOnAttribute($attribute, $subject);
            if ($granted === true) {
                return self::ACCESS_GRANTED;
            }
            if ($granted === false) {
                return self::ACCESS_DENIED;
            }
        }
        return $vote;
    }

    abstract protected function voteOnAttribute($attribute, $object = null): ?bool;

    protected function isCreator($object): bool
    {
        return $object instanceof BlameableInterface && $object->getCreatedBy() === $this->getUser();
    }

    protected function isSuperAdmin(): bool
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    protected function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    protected function isUser(): bool
    {
        return $this->hasRole('ROLE_USER');
    }

    protected function hasRole(string $roleName): bool
    {
        foreach ($this->getToken()->getRoles() as $role) {
            if ($roleName === $role->getRole()) {
                return true;
            }
        }
        return false;
    }

    protected function getUser()
    {
        return $this->getToken()->getUser();
    }

    protected function isLoggedIn(): bool
    {
        return $this->getUser() instanceof UserInterface;
    }

    protected function getToken(): TokenInterface
    {
        if (!$this->token) {
            throw new \RuntimeException('Cannot retrieve token before "vote" method has been called.');
        }
        return $this->token;
    }
}
