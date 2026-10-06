<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Entity\Club;
use App\Entity\Group;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final readonly class ClubPresenter
{
    public function __construct(private UploaderHelper $uploaderHelper)
    {
    }

    /**
     * @return array{id: int|null, name: string|null}
     */
    public function reference(Club $club): array
    {
        return ['id' => $club->getId(), 'name' => $club->getName()];
    }

    /**
     * @return array{id: int|null, name: string|null, city: string|null, contact_email: string|null, ffta_code: string|null, primary_color: string|null, logo_url: string|null}
     */
    public function summary(Club $club): array
    {
        return [
            ...$this->reference($club),
            'city' => $club->getCity(),
            'contact_email' => $club->getContactEmail(),
            'ffta_code' => $club->getFftaCode(),
            'primary_color' => $club->getPrimaryColor(),
            'logo_url' => null !== $club->getLogoName() ? $this->uploaderHelper->asset($club, 'logo') : null,
        ];
    }

    /**
     * @return array{id: int|null, name: string|null}
     */
    public function groupReference(Group $group): array
    {
        return ['id' => $group->getId(), 'name' => $group->getName()];
    }
}
