<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Branché sur le firewall "main" (formulaire /login du back-office) :
 * refuse la connexion aux comptes sans ROLE_ADMIN au lieu de les laisser tomber sur une 403.
 */
class AdminUserChecker implements UserCheckerInterface
{
    public function __construct(
        private RoleHierarchyInterface $roleHierarchy
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        $roles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

        if (!\in_array('ROLE_ADMIN', $roles, true)) {
            throw new CustomUserMessageAccountStatusException('Accès réservé aux administrateurs.');
        }
    }
}
