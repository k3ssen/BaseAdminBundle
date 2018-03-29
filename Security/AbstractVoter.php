<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Security;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * AbstractVoter similar to the Voter of the symfony core:
 * https://github.com/symfony/security/blob/v4.0.6/Core/Authorization/Voter/Voter.php
 *
 * This class is different in that it uses the UserRoleInfoProvider and has the support method implemented.
 * The voteOnAttribute gets different arguments to encourage using the UserRoleInfoProvider
 */
abstract class AbstractVoter implements VoterInterface
{
    /** @var UserRoleInfoProvider */
    protected $userInfo;

    public function __construct(UserRoleInfoProvider $userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) {
            if (!$this->supports($attribute, $subject)) {
                continue;
            }

            // as soon as at least one attribute is supported, default is to deny access
            $vote = self::ACCESS_DENIED;

            // ensure the voteOnAttribute implementations only have to deal with (entity) objects
            $object = is_string($subject) ? null : $subject;

            if ($this->voteOnAttribute($this->userInfo, $attribute, $object)) {
                // grant access as soon as at least one attribute returns a positive response
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * Default rules for returning true:
     *  - The $attribute must start with the value returned by getAttributePrefix
     *  - The $subject must be an instance or string matching the entity class.
     *
     * @param object|string $subject the entity object or name
     */
    protected function supports(string $attribute, $subject): bool
    {
        return strpos($attribute, $this->getAttributePrefix()) !== false && is_a($subject, $this->getEntity(), true);
    }

    abstract protected function getEntity(): string;

    protected function getAttributePrefix(): string
    {
        return strtoupper(Inflector::tableize((new \ReflectionClass($this->getEntity()))->getShortName()));
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param object|null $subject the entity object
     */
    abstract protected function voteOnAttribute(UserRoleInfoProvider $userInfo, $attribute, $object = null): bool;
}
