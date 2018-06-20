<?php

namespace Vib\SymfUser\DataFixtures;

use Vib\SymfUser\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UsersFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = new user();
        $user->setUsername('vincent.baron@ville-creteil.fr');
        $user->setPlainPassword('azerty123');
        $manager->persist($user);

        $manager->flush();
    }
}