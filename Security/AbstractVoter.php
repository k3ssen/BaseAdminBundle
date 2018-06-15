<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Security;

use K3ssen\BaseAdminBundle\Model\BlameableEntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractVoter implements VoterWithStrategyInterface
{
    /** @var TokenInterface */
    protected $token;
    protected $strategy;

    public function setStrategy($voterStrategy)
    {
        $this->strategy = $voterStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;
        $this->token = $token;

        $grantedVotes = 0;
        $deniedVotes = 0;
        foreach ($attributes as $attribute) {
            if (!$this->supports($attribute, $subject)) {
                continue;
            }
            $granted = $this->voteOnAttribute($attribute, $subject);
            if ($granted === true) {
                $vote = self::ACCESS_GRANTED;
                // Using the affirmative strategy, only 1 attribute needs to be granted
                if ($this->strategy === AccessDecisionManager::STRATEGY_AFFIRMATIVE) {
                    break;
                }
                $grantedVotes++;
            }
            if ($granted === false) {
                $vote = self::ACCESS_DENIED;
                // Using the unanimous strategy, only access is denied if there's any denied attribute.
                if ($this->strategy === AccessDecisionManager::STRATEGY_UNANIMOUS) {
                    break;
                }
                $deniedVotes++;
            }
        }
        $this->token = null;
        // Using consensus-strategy, access is granted when there are more votes for granted than there are votes for denied
        // see https://symfony.com/doc/4.2/security/voters.html#changing-the-access-decision-strategy
        if ($this->strategy === AccessDecisionManager::STRATEGY_CONSENSUS) {
            if ($grantedVotes > $deniedVotes) {
                $vote = self::ACCESS_GRANTED;
            } elseif ($deniedVotes > 0) {
                $vote = self::ACCESS_DENIED;
            }
        }
        return $vote;
    }
    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported.
     */
    protected function supports($attribute, $subject): bool
    {
        return true;
    }

    abstract protected function voteOnAttribute($attribute, $object = null): ?bool;

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

    protected function isCreator($object): bool
    {
        if ($object instanceof BlameableEntityInterface) {
            return $object->getCreatedBy() === $this->getUser();
        }
        return false;
    }

    protected function getToken(): TokenInterface
    {
        if (!$this->token) {
            throw new \RuntimeException('Cannot retrieve token before "vote" method has been called.');
        }
        return $this->token;
    }
}
