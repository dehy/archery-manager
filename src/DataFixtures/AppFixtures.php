<?php

namespace App\DataFixtures;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\DBAL\Types\UserRoleType;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\LicenseHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function __construct(private readonly LicenseHelper $licenseHelper)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr');

        $admin = new User();
        $admin->setRoles([UserRoleType::ADMIN, UserRoleType::USER])
            ->setEmail('admin@acme.org')
            ->setPassword('$2y$13$AXw36f3KDVUmodygNIT.Su5vCtf1OU2ftjFRsTZmvdsqkw0UhtRKi') // admin
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setGender(GenderType::MALE);
        $manager->persist($admin);

        $licensee = new Licensee();
        $licensee
            ->setUser($admin)
            ->setGender($admin->getGender())
            ->setFirstname($admin->getFirstname())
            ->setLastname($admin->getLastname())
            ->setBirthdate($faker->dateTimeInInterval('-39 years', '-21 years'))
            ->setFftaId(123456)
            ->setFftaMemberCode('0123456F');
        $manager->persist($licensee);

        $license = new License();
        $license
            ->setLicensee($licensee)
            ->setSeason(2024)
            ->setType(LicenseType::ADULTES_COMPETITION)
            ->setActivities([LicenseActivityType::CL])
            ->setAgeCategory(LicenseAgeCategoryType::SENIOR_1)
            ->setCategory(LicenseCategoryType::ADULTES);
        $manager->persist($license);

        for ($i = 1; $i <= 5; ++$i) {
            $gender = random_int(0, 1) ? GenderType::MALE : GenderType::FEMALE;

            $user = new User();
            $user->setRoles([UserRoleType::USER])
                ->setEmail(sprintf('user%d@acme.org', $i))
                ->setPassword('$2y$13$CGDD6CfkN8pHT/hKhml2RuA28Ba48QE86SlrjPssIcfXmRsNrzh1W') // user
                ->setFirstname($faker->firstName(GenderType::MALE === $gender ? 'male' : 'female'))
                ->setLastname($faker->lastName())
                ->setGender($gender);
            $manager->persist($user);

            $fftaId = $faker->randomNumber(7, true);
            $birthdate = $faker->dateTimeInInterval('-65 years', '-18 years');

            $licensee = new Licensee();
            $licensee
                ->setUser($user)
                ->setGender($user->getGender())
                ->setFirstname($user->getFirstname())
                ->setLastname($user->getLastname())
                ->setBirthdate($birthdate)
                ->setFftaId($fftaId)
                ->setFftaMemberCode($fftaId.strtoupper($faker->randomLetter()));
            $manager->persist($licensee);

            $license = new License();
            $license
                ->setLicensee($licensee)
                ->setSeason(2024)
                ->setType($this->licenseHelper->licenseTypeForBirthdate($birthdate, (bool) random_int(0, 1)))
                ->setActivities([LicenseActivityType::CL])
                ->setAgeCategory($this->licenseHelper->ageCategoryForBirthdate($birthdate))
                ->setCategory($this->licenseHelper->licenseCategoryTypeForBirthdate($birthdate));
            $manager->persist($license);
        }

        $manager->flush();
    }
}
