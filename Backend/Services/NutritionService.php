<?php
class MealSuggestionService
{

    public static function getSuggestions(mysqli $connection, int $userId): array
    {
        // Get parsed entries from the past week
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

        $sql = "SELECT pe.meal, pe.created_at 
                FROM parsed_entries pe
                INNER JOIN user_entries ue ON pe.user_entry_id = ue.id
                WHERE ue.user_id = ? 
                AND pe.meal IS NOT NULL 
                AND ue.created_at >= ?
                ORDER BY ue.created_at DESC";

        $stmt = $connection->prepare($sql);
        $stmt->bind_param("is", $userId, $weekAgo);
        $stmt->execute();
        $result = $stmt->get_result();

        $meals = [];
        while ($row = $result->fetch_assoc()) {
            $meals[] = [
                'meal' => $row['meal'],
                'date' => $row['created_at']
            ];
        }

        // If no meals found, return early
        if (empty($meals)) {
            return [
                'ok' => false,
                'error' => 'No meal data found in the past week'
            ];
        }

        // Prepare the meal summary for AI
        $mealSummary = self::formatMealsForAI($meals);

        // Call AI to get suggestions
        return self::callAIForSuggestions($mealSummary);
    }


    private static function formatMealsForAI(array $meals): string
    {
        $summary = "User's meals from the past week:\n\n";

        foreach ($meals as $index => $meal) {
            $date = date('l, M j', strtotime($meal['date']));
            $summary .= ($index + 1) . ". $date: {$meal['meal']}\n";
        }

        return $summary;
    }

    private static function callAIForSuggestions(string $mealSummary): array
    {
        $openaiKey = "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 " ?: null;

        if (!$openaiKey) {
            return ['ok' => false, 'error' => 'OpenAI API key missing'];
        }

        $systemPrompt = <<<SYS
You are a professional nutritionist and health advisor. Analyze the user's meal history from the past week and provide personalized, healthy meal suggestions.

Your response should:
1. Briefly analyze their current eating patterns (2-3 sentences)
2. Suggest 5 healthy meal ideas that:
   - Complement or improve upon their current diet
   - Include a variety of nutrients
   - Are practical and easy to prepare
   - Consider any patterns you notice (e.g., lack of vegetables, too much processed food, good protein intake, etc.)

Format your response as JSON with this structure:
{
  "analysis": "Brief analysis of their diet patterns",
  "suggestions": [
    {
      "meal_name": "Name of the meal",
      "description": "Brief description and why it's beneficial",
      "key_nutrients": ["nutrient1", "nutrient2", "nutrient3"]
    }
  ],
  "general_tips": ["tip1", "tip2", "tip3"]
}

Be encouraging and positive in your tone. Make suggestions realistic and achievable.
SYS;

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $mealSummary]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$result) {
            return ['ok' => false, 'error' => "Failed to connect to OpenAI"];
        }

        $decoded = json_decode($result, true);

        if (isset($decoded["error"])) {
            return ['ok' => false, 'error' => $decoded["error"]["message"] ?? "Unknown error"];
        }

        $content = $decoded["choices"][0]["message"]["content"] ?? "";

        // Extract and parse JSON from response
        $suggestions = self::extractJSON($content);

        if (!$suggestions) {
            return [
                'ok' => false,
                'error' => 'Failed to parse AI response',
                'raw_content' => $content
            ];
        }

        return [
            'ok' => true,
            'data' => $suggestions
        ];
    }


    private static function extractJSON(string $text): ?array
    {
        // Try direct JSON decode first
        $direct = json_decode($text, true);
        if (is_array($direct)) {
            return $direct;
        }

        // Try to find JSON in the text
        $first = strpos($text, "{");
        $last = strrpos($text, "}");

        if ($first !== false && $last !== false) {
            $json = substr($text, $first, $last - $first + 1);
            $parsed = json_decode($json, true);
            return is_array($parsed) ? $parsed : null;
        }

        return null;
    }
}
