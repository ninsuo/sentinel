<?php

namespace App\Twig;

use App\Ai\AiProvider;
use App\Repository\SettingRepository;
use App\Settings\SettingKeys;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    private ?string $defaultProvider = null;

    public function __construct(
        private readonly SettingRepository $settings,
    ) {
    }

    public function getGlobals() : array
    {
        $value = $this->defaultProvider ??= $this->settings->get(SettingKeys::DEFAULT_PROVIDER, AiProvider::OPENAI->value);

        // Fallback if DB contains garbage
        $provider = AiProvider::tryFrom($value) ?? AiProvider::OPENAI;

        return [
            'default_provider' => $provider,
            'available_providers' => AiProvider::cases(),
        ];
    }
}
