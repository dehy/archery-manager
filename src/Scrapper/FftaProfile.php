<?php

namespace App\Scrapper;

use DateTime;

class FftaProfile
{
    public ?int $id = null;
    public ?string $codeAdherent = null;
    public ?string $email = null;
    public ?string $nom = null;
    public ?string $prenom = null;
    public ?string $mobile = null;
    public ?DateTime $dateNaissance = null;
    public ?string $sexe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): FftaProfile
    {
        $this->id = $id;

        return $this;
    }

    public function setCodeAdherent(?string $codeAdherent): FftaProfile
    {
        $this->codeAdherent = $codeAdherent;

        return $this;
    }

    public function getCodeAdherent(): ?string
    {
        return $this->codeAdherent;
    }

    public function setEmail(?string $email): FftaProfile
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setNom(?string $nom): FftaProfile
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setPrenom(?string $prenom): FftaProfile
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setMobile(?string $mobile): FftaProfile
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setDateNaissance(?DateTime $dateNaissance): FftaProfile
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getDateNaissance(): ?DateTime
    {
        return $this->dateNaissance;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): FftaProfile
    {
        $this->sexe = $sexe;

        return $this;
    }
}
