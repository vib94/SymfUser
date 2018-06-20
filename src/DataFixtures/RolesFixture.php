<?php
// src/DataFixtures/AppFixtures.php
namespace Vib\SymfUser\DataFixtures;

use Vib\SymfUser\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class RolesFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $product = new Role();
        $product->setName('ROLE_USER');
        $manager->persist($product);

        // create 20 products! Bam!
        /*for ($i = 0; $i < 20; $i++) {
            $product = new Product();
            $product->setName('product '.$i);
            $product->setPrice(mt_rand(10, 100));
            $manager->persist($product);
        }*/

        $manager->flush();
    }
}