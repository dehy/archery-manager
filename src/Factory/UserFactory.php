<?php

namespace App\Factory;

use App\Entity\User;
use App\Scrapper\FftaProfile;

class UserFactory
{
    public static function createFromFftaProfile(FftaProfile $profile): User
    {
        return (new User())
            ->setEmail($profile->getEmail())
            ->setFirstname($profile->getPrenom())
            ->setLastname($profile->getNom())
            ->setGender($profile->getSexe())
            ->setPhoneNumber($profile->getMobile())
            ->setRoles(['ROLE_USER'])
            ->setPassword('!!')
        ;
    }
}
