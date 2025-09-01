<?php

declare(strict_types=1);

// Simple test to see the mapping order and results
$mapping = [
    '<1965-01-01' => 'SENIOR_3',
    '>=1965-01-01_<=1984-12-31' => 'SENIOR_2',
];

$birthdate = new \DateTimeImmutable('1965-01-01');

echo "Testing birthdate: " . $birthdate->format('Y-m-d') . "\n";

foreach ($mapping as $dateKey => $ageCategory) {
    echo "\nTesting condition: $dateKey -> $ageCategory\n";
    
    $parts = explode('_', (string) $dateKey);
    $leftPart = $parts[0];
    $rightPart = $parts[1] ?? null;
    
    echo "  Left part: $leftPart\n";
    echo "  Right part: " . ($rightPart ?? 'null') . "\n";
    
    $after = null;
    $before = null;
    $afterInclusive = false;
    $beforeInclusive = false;
    
    if (null === $rightPart || '' === $rightPart || '0' === $rightPart) {
        if (str_starts_with($leftPart, '>=')) {
            $after = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 2, 10));
            $afterInclusive = true;
            echo "  After (inclusive): " . $after->format('Y-m-d') . "\n";
        } elseif (str_starts_with($leftPart, '<=')) {
            $before = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 2, 10));
            $beforeInclusive = true;
            echo "  Before (inclusive): " . $before->format('Y-m-d') . "\n";
        } elseif (str_starts_with($leftPart, '>')) {
            $after = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 1, 10));
            $afterInclusive = false;
            echo "  After (exclusive): " . $after->format('Y-m-d') . "\n";
        } elseif (str_starts_with($leftPart, '<')) {
            $before = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 1, 10));
            $beforeInclusive = false;
            echo "  Before (exclusive): " . $before->format('Y-m-d') . "\n";
        }
    } else {
        // Handle range
        if (str_starts_with($leftPart, '>=')) {
            $after = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 2, 10));
            $afterInclusive = true;
            echo "  After (inclusive): " . $after->format('Y-m-d') . "\n";
        } else {
            $after = \DateTimeImmutable::createFromFormat('Y-m-d', substr($leftPart, 1, 10));
            $afterInclusive = false;
            echo "  After (exclusive): " . $after->format('Y-m-d') . "\n";
        }
        
        if (str_starts_with($rightPart, '<=')) {
            $before = \DateTimeImmutable::createFromFormat('Y-m-d', substr($rightPart, 2, 10));
            $beforeInclusive = true;
            echo "  Before (inclusive): " . $before->format('Y-m-d') . "\n";
        } else {
            $before = \DateTimeImmutable::createFromFormat('Y-m-d', substr($rightPart, 1, 10));
            $beforeInclusive = false;
            echo "  Before (exclusive): " . $before->format('Y-m-d') . "\n";
        }
    }
    
    $afterCheck = ($after === null) || 
                 ($afterInclusive ? $birthdate >= $after : $birthdate > $after);
    $beforeCheck = ($before === null) || 
                  ($beforeInclusive ? $birthdate <= $before : $birthdate < $before);

    echo "  After check: " . ($afterCheck ? 'true' : 'false') . "\n";
    echo "  Before check: " . ($beforeCheck ? 'true' : 'false') . "\n";
    echo "  Final result: " . (($afterCheck && $beforeCheck) ? 'MATCH' : 'NO MATCH') . "\n";
    
    if ($afterCheck && $beforeCheck) {
        echo "\n*** FOUND MATCH: $ageCategory ***\n";
        break;
    }
}
