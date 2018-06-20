<?php

namespace Vib\SymfUser\Security;

use Vib\SymfUser\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByUsername($user)
    {
        $user = $this->findUserBy(['username' => $user]);

        if (!$user) {
            throw new UsernameNotFoundException(
                sprintf(
                    'User with "%s" does not exist.',
                    $email
                )
            );
        }

        return $user;
    }

    public function loadUserByEmail($user)
    {
        $user = $this->findUserBy(['email' => $user]);

        if (!$user) {
            throw new UsernameNotFoundException(
                sprintf(
                    'User with "%s" does not exist.',
                    $email
                )
            );
        }

        return $user;
    }

    private function findUserBy(array $options)
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy($options);

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (null === $reloadedUser = $this->findUserBy(['id' => $user->getId()])) {
            throw new UsernameNotFoundException(
                sprintf(
                    'User with ID "%s" could not be reloaded.',
                    $user->getId()
                )
            );
        }

        return $reloadedUser;
    }

    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
