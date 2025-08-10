<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ApplicantRegistration;

/**
 * Processor for handling applicant registration requests.
 */
class ApplicantRegistrationProcessor implements ProcessorInterface
{
    /**
     * @param ApplicantRegistration $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // TODO: Implement applicant registration logic
        // For now, just return the data to avoid errors
        return $data;
    }
}
