<?php

namespace App\Scrapper;

class FftaResult
{
    private int $position;
    private string $name;
    private string $club;
    private string $license;
    private string $category;
    private int $distance;
    private int $size;
    private int $score1;
    private int $score2;
    private int $total;
    private int $nb10;
    private int $nb10p;

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return FftaResult
     */
    public function setPosition(int $position): FftaResult
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FftaResult
     */
    public function setName(string $name): FftaResult
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getClub(): string
    {
        return $this->club;
    }

    /**
     * @param string $club
     * @return FftaResult
     */
    public function setClub(string $club): FftaResult
    {
        $this->club = $club;
        return $this;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     * @return FftaResult
     */
    public function setLicense(string $license): FftaResult
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return FftaResult
     */
    public function setCategory(string $category): FftaResult
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getDistance(): int
    {
        return $this->distance;
    }

    /**
     * @param int $distance
     * @return FftaResult
     */
    public function setDistance(int $distance): FftaResult
    {
        $this->distance = $distance;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return FftaResult
     */
    public function setSize(int $size): FftaResult
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return int
     */
    public function getScore1(): int
    {
        return $this->score1;
    }

    /**
     * @param int $score1
     * @return FftaResult
     */
    public function setScore1(int $score1): FftaResult
    {
        $this->score1 = $score1;
        return $this;
    }

    /**
     * @return int
     */
    public function getScore2(): int
    {
        return $this->score2;
    }

    /**
     * @param int $score2
     * @return FftaResult
     */
    public function setScore2(int $score2): FftaResult
    {
        $this->score2 = $score2;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     * @return FftaResult
     */
    public function setTotal(int $total): FftaResult
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getNb10(): int
    {
        return $this->nb10;
    }

    /**
     * @param int $nb10
     * @return FftaResult
     */
    public function setNb10(int $nb10): FftaResult
    {
        $this->nb10 = $nb10;
        return $this;
    }

    /**
     * @return int
     */
    public function getNb10p(): int
    {
        return $this->nb10p;
    }

    /**
     * @param int $nb10p
     * @return FftaResult
     */
    public function setNb10p(int $nb10p): FftaResult
    {
        $this->nb10p = $nb10p;
        return $this;
    }
}
