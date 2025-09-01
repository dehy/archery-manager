<?php

require_once 'vendor/autoload.php';

use App\Helper\LicenseHelper;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;

// Create mock objects
$licenseeHelper = $this->createMock(LicenseeHelper::class);
$seasonHelper = $this->createMock(SeasonHelper::class);
$seasonHelper->method('getSelectedSeason')->willReturn(2024);

$licenseHelper = new LicenseHelper($licenseeHelper, $seasonHelper);

$birthdate = new \DateTimeImmutable('1965-01-01');
echo "Testing birthdate: " . $birthdate->format('Y-m-d') . "\n";

try {
    $result = $licenseHelper->ageCategoryForBirthdate($birthdate);
    echo "Result: " . $result . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
