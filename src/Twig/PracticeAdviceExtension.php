<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\PracticeAdviceRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PracticeAdviceExtension extends AbstractExtension
{
    public function __construct(private readonly PracticeAdviceRenderer $renderer)
    {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            // The output is sanitized HTML: safe to print without escaping.
            new TwigFilter('advice_html', $this->renderer->toHtml(...), ['is_safe' => ['html']]),
        ];
    }
}
