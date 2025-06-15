<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // $user = new User();
        // $user->setEmail('xxx@gmail.com');
        // $user->setRoles(['ROLE_xxx']);
        // $user->setIsVerified(true); // facultatif si tu veux sauter la vérif email
        // $user->setPassword($this->hasher->hashPassword($user, 'xxxxxxx'));

        // $manager->persist($user);

        $manager->flush();
    }
}
