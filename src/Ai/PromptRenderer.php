<?php

namespace App\Ai;

use Twig\Environment;

final readonly class PromptRenderer
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderSystem(array $context) : string
    {
        return trim($this->twig->render('ai/feature_run/system.txt.twig', $context));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderUser(array $context) : string
    {
        return trim($this->twig->render('ai/feature_run/user.txt.twig', $context));
    }
}
