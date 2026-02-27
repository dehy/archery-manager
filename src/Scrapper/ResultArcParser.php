<?php

declare(strict_types=1);

namespace App\Scrapper;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\Entity\Result;
use App\Exception\ParseException;
use Smalot\PdfParser\Parser;

/**
 * Parse Result'Arc PDF files to extract results.
 */
class ResultArcParser
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @return ResultArcLine[]
     *
     * @throws \Exception
     */
    public function parseFile(string $filepath): array
    {
        $pdf = $this->parser->parseFile($filepath);

        $results = [];
        $re = '/'.$this->searchPattern().'/m';
        foreach (explode(\PHP_EOL, $pdf->getText()) as $line) {
            dump($line);
            if (1 === preg_match($re, $line, $matches)) {
                $score = (int) $matches[1];
                $ageCategory = $matches[3];
                $activity = $matches[4];
                $fftaCode = $matches[5];
                $resultArcLine = new ResultArcLine(
                    $fftaCode,
                    $ageCategory,
                    $activity,
                    $score,
                );
                $results[$fftaCode] = $resultArcLine;
            }
        }

        dd($results);

        return $results;
    }

    /**
     * @return Result[]
     *
     * @throws \Exception
     */
    public function parseContent(string $fileContent): array
    {
        $this->parser->parseContent($fileContent);

        return [];
    }

    protected function parseCategory(string $category): array
    {
        $found = preg_match(
            '/'.$this->categoryPattern().'/',
            $category,
            $matches,
        );

        if (0 === $found || false === $found) {
            throw new ParseException('Cannot parse category');
        }

        return [$matches[1], $matches[2]];
    }

    private function searchPattern(): string
    {
        return \sprintf(
            "^[-'A-ZÀ-ž ]+ (\\d{2,3})(  \\d{1,2})?%s .* (\\d{6}\\w)( \\d+)?$",
            $this->categoryPattern(),
        );
    }

    private function categoryPattern(): string
    {
        $ageCategories = implode('|', LicenseAgeCategoryType::getValues());
        $activityTypes = implode('|', LicenseActivityType::getValues());

        return \sprintf('(%s)[HF](%s)', $ageCategories, $activityTypes);
    }
}
