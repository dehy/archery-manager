<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    #[\Override]
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->isInstanceof(...)),
        ];
    }

    public function isInstanceof($var, $instance): bool
    {
        return $var instanceof $instance;
    }
}
