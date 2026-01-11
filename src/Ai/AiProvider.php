<?php

namespace App\Ai;

enum AiProvider: string
{
    case OPENAI = 'openai';
    case GEMINI = 'gemini';

    public function label() : string
    {
        return match ($this) {
            self::OPENAI => 'OpenAI',
            self::GEMINI => 'Gemini',
        };
    }
}
