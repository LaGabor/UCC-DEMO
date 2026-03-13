<?php

namespace App\Services;

use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Data\Communication\MessageDecisionData;
use App\Enums\MessageStrength;
use App\Models\Conversation;
use App\Models\ConversationMessage;

class SystemMessageBotService implements SystemMessageBotServiceInterface
{
    private const ACCENT_MAP = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ű' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i',
        'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u',
    ];

    public function resolve(Conversation $conversation, ConversationMessage $userMessage): MessageDecisionData
    {
        $rawText = trim($userMessage->message_text ?? '');
        if ($rawText === '') {
            return new MessageDecisionData(MessageStrength::ERROR, null);
        }

        $textForMatching = $this->normalizeInputForMatching($rawText);
        if ($textForMatching === '') {
            return new MessageDecisionData(MessageStrength::VERY_WEAK, null);
        }

        $words = preg_split('/\s+/u', trim($textForMatching), -1, PREG_SPLIT_NO_EMPTY);
        $searchWords = config('system_message_bot.search_words', []);
        $answers = config('system_message_bot.answers', []);
        $minStrength = (int) config('system_message_bot.min_strength_to_respond', 2);

        $scopeScores = [];
        $langScores = ['hu' => 0, 'en' => 0];

        $wordCount = count($words);
        for ($i = 0; $i < $wordCount; $i++) {
            $phrase1 = $words[$i];
            $this->matchKeyword($phrase1, $searchWords, $scopeScores, $langScores);

            if ($i + 1 < $wordCount) {
                $phrase2 = $words[$i] . ' ' . $words[$i + 1];
                $this->matchKeyword($phrase2, $searchWords, $scopeScores, $langScores);
            }

            if ($i + 2 < $wordCount) {
                $phrase3 = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                $this->matchKeyword($phrase3, $searchWords, $scopeScores, $langScores);
            }
        }

        if ($scopeScores === []) {
            return new MessageDecisionData(MessageStrength::VERY_WEAK, null);
        }

        $bestScope = (string) array_key_first($scopeScores);
        $bestScore = $scopeScores[$bestScope];
        foreach ($scopeScores as $scope => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestScope = $scope;
            }
        }

        $strength = $this->scoreToStrength($bestScore);
        if ($strength->value < $minStrength) {
            return new MessageDecisionData(MessageStrength::VERY_WEAK, null);
        }

        $useLang = $langScores['hu'] >= $langScores['en'] ? 'hu' : 'en';
        $message = $answers[$bestScope][$useLang] ?? $answers[$bestScope]['hu'] ?? $answers[$bestScope]['en'] ?? null;

        if ($message === null || $message === '') {
            return new MessageDecisionData(MessageStrength::VERY_WEAK, null);
        }

        return new MessageDecisionData($strength, $message);
    }

    private function matchKeyword(
        string $phrase,
        array $searchWords,
        array &$scopeScores,
        array &$langScores
    ): void {
        if (! isset($searchWords[$phrase])) {
            return;
        }

        foreach ($searchWords[$phrase] as $entry) {
            $scope = $entry['scope'] ?? '';
            $lang = $entry['lang'] ?? 'hu';

            if ($scope !== '') {
                $scopeScores[$scope] = ($scopeScores[$scope] ?? 0) + 1;
            }

            if (isset($langScores[$lang])) {
                $langScores[$lang]++;
            }
        }
    }

    private function normalizeInputForMatching(string $input): string
    {
        $s = mb_strtolower(trim($input), 'UTF-8');
        $s = strtr($s, self::ACCENT_MAP);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return $s;
    }

    private function scoreToStrength(int $score): MessageStrength
    {
        return match (true) {
            $score >= 4 => MessageStrength::VERY_STRONG,
            $score >= 3 => MessageStrength::STRONG,
            $score >= 2 => MessageStrength::NORMAL,
            $score >= 1 => MessageStrength::NORMAL,
            default => MessageStrength::VERY_WEAK,
        };
    }
}
