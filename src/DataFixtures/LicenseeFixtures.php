<?php

namespace App\DataFixtures;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\UserRoleType;
use App\Entity\Club;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Helper\LicenseHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class LicenseeFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly LicenseHelper $licenseHelper)
    {
    }

    public function getDependencies()
    {
        return [
            ClubFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr');
        /** @var Club $defaultClub */
        $defaultClub = $this->getReference(ClubFixtures::DEFAULT_CLUB_REFERENCE);
        $nottinghamClub = $this->getReference(ClubFixtures::NOTTINGHAM_CLUB_REFERENCE);

        /** @var Group $group1 */
        $group1 = $this->getReference(GroupFixtures::DEFAULT_GROUP_1);

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
            ->setFftaMemberCode('123456F');
        $manager->persist($licensee);

        $users = [
            [GenderType::MALE, 'Robin', 'de Locksley', 'robin.delocksley@joyeux-compagnons.co.uk', $defaultClub, $group1],
            [GenderType::MALE, 'Frère', 'Tuck', 'frere.tuck@joyeux-compagnons.co.uk', $defaultClub],
            [GenderType::MALE, 'Petit', 'Jean', 'petit.jean@joyeux-compagnons.co.uk', $defaultClub],
            [GenderType::FEMALE, 'Belle', 'Marianne', 'belle.marianne@joyeux-compagnons.co.uk', $defaultClub],
            [GenderType::MALE, 'Shérif', 'de Nottingham', 'sherif.denottingham@nottingham.co.uk', $nottinghamClub],
            [GenderType::MALE, 'Prince', 'Jean', 'prince.jean@nottingham.co.uk', $nottinghamClub],
            [GenderType::MALE, 'Guy', 'de Gisbourne', 'guy.degisbourne@nottingham.co.uk', $nottinghamClub],
        ];

        for ($i = 0; $i < \count($users); ++$i) {
            $user = new User();
            $user->setRoles([UserRoleType::USER])
                ->setEmail($users[$i][3])
                ->setPassword('$2y$13$CGDD6CfkN8pHT/hKhml2RuA28Ba48QE86SlrjPssIcfXmRsNrzh1W') // user
                ->setFirstname($users[$i][1])
                ->setLastname($users[$i][2])
                ->setGender($users[$i][0]);
            $manager->persist($user);

            $fftaId = $faker->randomNumber(6);
            $birthdate = $faker->dateTimeInInterval('-65 years', '-18 years');

            $licensee = new Licensee();
            $licensee
                ->setUser($user)
                ->setGender($user->getGender())
                ->setFirstname($user->getFirstname())
                ->setLastname($user->getLastname())
                ->setBirthdate($birthdate)
                ->setFftaId($fftaId)
                ->setFftaMemberCode($fftaId.'S');
            if (isset($users[$i][5])) {
                $licensee->addGroup($users[$i][5]);
            }
            $manager->persist($licensee);

            $license = new License();
            $license
                ->setClub($users[$i][4])
                ->setLicensee($licensee)
                ->setSeason(Season::seasonForDate(new \DateTimeImmutable()))
                ->setType($this->licenseHelper->licenseTypeForBirthdate($birthdate, $faker->boolean()))
                ->setActivities([LicenseActivityType::CL])
                ->setAgeCategory($this->licenseHelper->ageCategoryForBirthdate($birthdate))
                ->setCategory($this->licenseHelper->licenseCategoryTypeForBirthdate($birthdate));
            $manager->persist($license);
        }

        $manager->flush();
    }
}
