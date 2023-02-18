<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AnonymousVoter extends Voter
{
    public const ANONYMOUS_USER = 'IS_ANONYMOUS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ANONYMOUS_USER;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        if (!$token->getUser() instanceof UserInterface) {
            return true;
        }

        return false;
    }
}
