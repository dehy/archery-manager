<?php

namespace App\Command;

use App\Entity\Licensee;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: 'app:import:users',
        description: 'Add a short description for your command',
    ),
]
class ImportUsersCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('csvFile', InputArgument::OPTIONAL, 'CSV file');
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $csvFile = $input->getArgument('csvFile');

        $userRepository = $this->entityManager->getRepository(User::class);

        if (($handle = fopen($csvFile, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $email = $data[0];
                $roles = json_decode((string) $data[1], true, 512, JSON_THROW_ON_ERROR);
                $password = $data[2];
                $gender = $data[3];
                $lastname = $data[4];
                $firstname = $data[5];
                $birthdate = $data[6];
                $is_verified = $data[7];
                $phone_number = $data[8];
                $ffta_member_code = $data[9];
                $ffta_id = $data[10];

                $user = $userRepository->findOneBy(['email' => $email]);
                if (!$user) {
                    $user = new User();
                    $user
                        ->setEmail($email)
                        ->setRoles(['ROLE_USER'])
                        ->setPassword('!!')
                        ->setGender($gender)
                        ->setLastname($lastname)
                        ->setFirstname($firstname);
                    $this->entityManager->persist($user);
                }
                $user->setPhoneNumber($phone_number);

                $licensee = $user
                    ->getLicensees()
                    ->filter(fn (Licensee $l) => $l->getFirstname() === $user->getFirstname())
                    ->first();

                if (!$licensee) {
                    $licensee = new Licensee();
                    $licensee->setUser($user);
                    $this->entityManager->persist($licensee);
                }

                $licensee
                    ->setGender($gender)
                    ->setLastname($lastname)
                    ->setFirstname($firstname)
                    ->setBirthdate(new DateTime($birthdate))
                    ->setFftaMemberCode($ffta_member_code)
                    ->setFftaId($ffta_id);

                $output->writeln(
                    sprintf(
                        'Importing %s %s',
                        $user->getFirstname(),
                        $user->getLastname(),
                    ),
                );
                $this->entityManager->flush();
            }

            fclose($handle);
        }

        return Command::SUCCESS;
    }
}
