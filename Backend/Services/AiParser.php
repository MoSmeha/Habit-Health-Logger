<?php

class AiParser
{

    public static function callOpenAi(string $userText): array
    {
        $openaiKey = "" ?: null;
        if (!$openaiKey) {
            return ['ok' => false, 'error' => 'OpenAI API key missing'];
        }

        $systemPrompt = <<<SYS
    You are a JSON extractor for a health-logging app. Your task is to analyze a user's free-text health log and return **only valid JSON** (no extra commentary) based on the information provided.

    Please use your best judgment to estimate and log the following details:

    - "slept" : string|null (The duration of sleep, e.g., "7 hours" , "6:30" , or null if not mentioned.)
    - "coffee" : integer|null (Your estimated total caffeine intake in milligrams (mg). Assume **100 mg per standard cup of coffee** unless the user specifies a different drink or quantity, or null if not mentioned.)
    - "walked" : string|null (The duration or distance of walking exercise, e.g., "45 mins" , "2 km" , or null.)
    - "meal" : string|null (A brief, descriptive summary of a meal or main food item mentioned, or null. Don't store a number only a string, )

    If any piece of information is not clearly present in the text, set its value to **null**. Ignore any info outside the info provided above. Always ensure the final output is **valid JSON**.
    SYS;

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userText]
            ],
            'temperature' => 0,
            'max_tokens' => 250
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer $openaiKey"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $result = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$result) {
            return ['ok' => false, 'error' => "Curl error"];
        }

        $decoded = json_decode($result, true);

        if (isset($decoded["error"])) {
            return ['ok' => false, 'error' => $decoded["error"]];
        }

        return [
            'ok' => true,
            'content' => $decoded["choices"][0]["message"]["content"] ?? "",
            'raw' => $decoded
        ];
    }

    public static function extract(string $text): ?array
    {
        $direct = json_decode($text, true);
        if (is_array($direct)) return $direct;

        $first = strpos($text, "{");
        $last = strrpos($text, "}");

        if ($first !== false && $last !== false) {
            $json = substr($text, $first, $last - $first + 1);
            $parsed = json_decode($json, true);
            return is_array($parsed) ? $parsed : null;
        }

        return null;
    }

    public static function normalize(array $parsed): array
    {
        return [
            'slept' => $parsed['slept'] ?? null,
            'coffee' => is_numeric($parsed['coffee'] ?? null) ? (int)$parsed['coffee'] : null,
            'walked' => $parsed['walked'] ?? null,
            'meal' => $parsed['meal'] ?? null
        ];
    }
}
