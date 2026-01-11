<?php

namespace App\Ai;

use App\Repository\SettingRepository;
use App\Settings\SettingKeys;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

readonly class AiClient
{
    public function __construct(
        #[AutowireLocator('ai.agent')]
        private ServiceLocator $agentsLocator,
        private SettingRepository $settingRepository,
    ) {
    }

    public function ask(string $systemPrompt, string $userPrompt) : ResultInterface
    {
        $messages = new MessageBag(
            Message::forSystem($systemPrompt),
            Message::ofUser($userPrompt),
        );

        $provider = $this->getProvider();

        /** @var AgentInterface $agent */
        $agent = $this->agentsLocator->get('ai.agent.'.$provider->value);

        return $agent->call($messages, [
            'max_output_tokens' => $provider->getTokens(),
        ]);
    }

    private function getProvider() : AiProvider
    {
        $raw = $this->settingRepository->get(
            SettingKeys::DEFAULT_PROVIDER,
            AiProvider::OPENAI->value
        );

        return AiProvider::tryFrom($raw ?? '') ?? AiProvider::OPENAI;
    }
}