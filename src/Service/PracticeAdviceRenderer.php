<?php

declare(strict_types=1);

namespace App\Service;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Turns the text of a piece of practice advice, written in Markdown, into HTML that is safe to display.
 *
 * Advice written before Markdown was HTML (from a rich-text editor). Markdown allows inline HTML, so that
 * text still renders the same; the sanitizer then keeps only safe formatting, whatever was typed.
 */
final readonly class PracticeAdviceRenderer
{
    private GithubFlavoredMarkdownConverter $converter;

    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.advice_sanitizer')]
        private HtmlSanitizerInterface $sanitizer,
    ) {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }

    public function toHtml(?string $markdown): string
    {
        if (null === $markdown || '' === trim($markdown)) {
            return '';
        }

        return $this->sanitizer->sanitize($this->converter->convert($markdown)->getContent());
    }
}
