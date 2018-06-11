<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Security;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

interface VoterWithStrategyInterface extends VoterInterface
{
    public function setStrategy($voterStrategy);
}