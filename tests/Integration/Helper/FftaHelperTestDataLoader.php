<?php

declare(strict_types=1);

namespace App\Tests\Integration\Helper;

use Faker\Factory;
use Faker\Generator;

trait FftaHelperTestDataLoader
{
    protected ?Generator $faker = null;

    protected array $licensees = [];

    public function getFaker(): Generator
    {
        if (!$this->faker) {
            $this->faker = Factory::create('fr_FR');
            $this->faker->seed(42);
        }

        return $this->faker;
    }

    public function getLicensees($count = 5): array
    {
        $licensees = [];
        $faker = $this->getFaker();
        for ($i = 0; $i < $count; ++$i) {
            $id = $faker->randomNumber(8, true);
            $licensees[] = $this->getLicensee($id);
        }

        return $licensees;
    }

    public function getLicensee(int $id): array
    {
        if (!isset($this->licensees[$id])) {
            $faker = $this->getFaker();
            $gender = $faker->randomElement(['male', 'female']);
            $letter = $faker->randomLetter();
            $this->licensees[$id] = [
                'id' => $id,
                'personne_id' => $id,
                'personne_url' => \sprintf('https://dirigeant.ffta.fr/personnes/fiche/%s/licences', $id),
                'code_adherent' => \sprintf('%s%s', substr($id, 0, 7), strtoupper((string) $letter)),
                'nom' => $faker->lastName(),
                'prenom' => $faker->firstName($gender),
                'sexe' => 'male' == $gender ? 'Masculin' : 'Féminin',
                'ddn' => $faker->dateTimeBetween('-65 years', '-12 years')->format('d/m/Y'),
                'photo' => false,
                'photo_url' => 'https://dirigeant.ffta.fr/storage/visuels/profil_homme.png',
                'etat' => 'Active',
                'etat_icon' => 'icon-checkmark3',
                'etat_color' => 'success',
                'date_demande' => '27/09/2024',
                'date_debut_validite' => '27/09/2024',
                'date_fin' => '31/08/2025',
                'saisie_par' => 'Club Unisport',
                'type_libelle' => 'Jeune',
                'discipline' => "<div class=\"wrap cursor-default \">\n    <div class=\"d-inline-block\">\n        \n        <div class=\"d-flex flex-nowrap\">\n            \n            \n            \n            <span class=\"inline-discipline text-uppercase badge badge-secondary badge-pill mb-1\" style=\"background: #8DB600\">\n                        <span title=\"\">\n                Tir à l&#039;arc\n            </span>\n                    </span>\n\n            \n                    </div>\n    </div>\n</div>\n\n",
                'categorie_age' => 'U18',
                'mutation' => 'Non',
                'surclassement' => 'Non',
                'mail' => $faker->email(),
                'telephone' => $faker->e164PhoneNumber(),
                'adresse' => '',
                'code_postal' => '',
                'commune' => '',
                'structure' => '1033093 - LES ARCHERS DE GUYENNE',
                'structure_url' => 'https://dirigeant.ffta.fr/structures/fiche/556',
                'representant_legal_1' => '',
                'representant_legal_2' => '',
                'ia' => true,
            ];
        }

        return $this->licensees[$id];
    }
}
