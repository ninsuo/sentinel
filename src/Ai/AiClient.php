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
        #[AutowireLocator(AgentInterface::class)]
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

        /** @var AgentInterface $agent */
        $agent = $this->agentsLocator->get($this->getProvider()->value);

        return $agent->call($messages);
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