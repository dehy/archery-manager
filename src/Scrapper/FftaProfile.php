<?php

namespace App\Scrapper;

class FftaProfile
{
    public ?int $id = null;
    public ?string $codeAdherent = null;
    public ?string $email = null;
    public ?string $nom = null;
    public ?string $prenom = null;
    public ?string $mobile = null;
    public ?\DateTime $dateNaissance = null;
    public ?string $sexe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setCodeAdherent(?string $codeAdherent): self
    {
        $this->codeAdherent = $codeAdherent;

        return $this;
    }

    public function getCodeAdherent(): ?string
    {
        return $this->codeAdherent;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setMobile(?string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setDateNaissance(?\DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }
}
