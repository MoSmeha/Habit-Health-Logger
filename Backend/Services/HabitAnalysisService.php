<?php
class HabitAnalysisService
{
    public static function analyzeHabits(mysqli $connection, int $userId): array
    {
        $habits = self::getActiveHabits($connection, $userId);
        if (empty($habits)) {
            return ['ok' => false, 'error' => 'No active habits found'];
        }

        $parsedEntries = self::getParsedEntriesFromLastWeek($connection, $userId);
        if (empty($parsedEntries)) {
            return ['ok' => false, 'error' => 'No activity data found in the past week'];
        }

        return self::callAIForAnalysis($habits, $parsedEntries);
    }

    private static function callAIForAnalysis(array $habits, array $entries): array
    {
        $openaiKey = "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";

        if (!$openaiKey) {
            return ['ok' => false, 'error' => 'OpenAI API key missing'];
        }

        // i pass the array as is, the ai determines if the habits are being tracked
        $contextData = json_encode([
            'active_habits_to_track' => $habits,
            'user_log_entries_past_week' => $entries
        ]);

        $systemPrompt = <<<SYS
You are a blunt habit coach. 
1. Analyze the provided JSON data containing the user's "active_habits" and their "log_entries" from the past week.
2. You must determine purely based on the context of the logs if they met their habits. (e.g., if habit is "Eat Veggies" and log says "ate spinach", that counts).
3. Return a SIMPLE JSON response:

{
  "summary": "2-3 line overview of their week",
  "following_well": [
    { "habit": "habit name", "reason": "1 sentence proof based on logs" }
  ],
  "not_following": [
    { "habit": "habit name", "reason": "1 sentence why" }
  ],
  "tips": ["tip1", "tip2"]
}

Keep it SHORT and HONEST.
SYS;

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Here is the raw data:\n\n" . $contextData]
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
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

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => "cURL error: " . $error_msg];
        }
        curl_close($ch);

        if (!$result) return ['ok' => false, 'error' => "Failed to connect to OpenAI"];

        $decoded = json_decode($result, true);
        $content = $decoded["choices"][0]["message"]["content"] ?? "";

        $aiAnalysis = json_decode($content, true);

        if (!$aiAnalysis && isset($decoded['error'])) {
            return ['ok' => false, 'error' => "OpenAI Error: " . $decoded['error']['message']];
        }

        return [
            'ok' => $aiAnalysis ? true : false,
            'data' => $aiAnalysis,
            'error' => $aiAnalysis ? null : 'Failed to parse AI JSON'
        ];
    }


    private static function getActiveHabits(mysqli $connection, int $userId): array
    {
        $query = $connection->prepare("SELECT name FROM habits WHERE user_id = ? AND active = 1");
        $query->bind_param("i", $userId);
        $query->execute();
        $result = $query->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private static function getParsedEntriesFromLastWeek(mysqli $connection, int $userId): array
    {
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $sql = "SELECT pe.meal, pe.slept, pe.walked, pe.coffee, ue.created_at 
                FROM parsed_entries pe
                JOIN user_entries ue ON pe.user_entry_id = ue.id
                WHERE ue.user_id = ? AND ue.created_at >= ?
                ORDER BY ue.created_at DESC";

        $row = $connection->prepare($sql);
        $row->bind_param("is", $userId, $weekAgo);
        $row->execute();
        $result = $row->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}
