<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Entry.php';
require_once __DIR__ . '/../models/ParsedEntry.php';

function jsonResponse(int $statusCode, array $payload)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function callOpenAiCurl(string $userText): array
{
    $openaiKey = "" ?: null;
    if (!$openaiKey) {
        return [
            'ok' => false,
            'error' => 'OpenAI API key not configured. Set OPENAI_API_KEY environment variable.'
        ];
    }

    $systemPrompt = <<<SYS
    You are a JSON extractor for a health-logging app. Your task is to analyze a user's free-text health log and return **only valid JSON** (no extra commentary) based on the information provided.

    Please use your best judgment to estimate and log the following details:

    - "slept" : string|null (The duration of sleep, e.g., "7 hours" , "6:30" , or null if not mentioned.)
    - "coffee" : integer|null (Your estimated total caffeine intake in milligrams (mg). Assume **100 mg per standard cup of coffee** unless the user specifies a different drink or quantity, or null if not mentioned.)
    - "walked" : string|null (The duration or distance of walking exercise, e.g., "45 mins" , "2 km" , or null.)
    - "meal" : string|null (A brief, descriptive summary of a meal or main food item mentioned, or null.)

    If any piece of information is not clearly present in the text, set its value to **null**. Ignore any info outside the info provided above. Always ensure the final output is **valid JSON**.
    SYS;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userText]
    ];

    $payload = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'temperature' => 0.0,
        'max_tokens' => 250
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$openaiKey}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $result = curl_exec($ch);
    if ($result === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => "Curl error: {$err}"];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($result, true);
    if ($decoded === null) {
        return ['ok' => false, 'error' => 'Invalid JSON response from OpenAI', 'raw' => $result, 'http_code' => $httpCode];
    }

    if (isset($decoded['error'])) {
        return ['ok' => false, 'error' => $decoded['error']];
    }

    $content = $decoded['choices'][0]['message']['content'] ?? ($decoded['choices'][0]['text'] ?? null);

    return ['ok' => true, 'content' => $content, 'raw_response' => $decoded];
}

function extractJsonFromModelOutput(string $text): ?array
{
    $text = trim($text);

    $direct = json_decode($text, true);
    if (is_array($direct)) return $direct;

    $first = strpos($text, '{');
    $last = strrpos($text, '}');
    if ($first !== false && $last !== false && $last > $first) {
        $substr = substr($text, $first, $last - $first + 1);
        $dec = json_decode($substr, true);
        if (is_array($dec)) return $dec;
    }

    return null;
}

function normalizeParsed(array $parsed): array
{
    $result = [
        'slept' => null,
        'coffee' => null,
        'walked' => null,
        'meal' => null
    ];

    if (array_key_exists('slept', $parsed)) {
        $s = $parsed['slept'];
        if ($s === null) $result['slept'] = null;
        else $result['slept'] = (string)$s;
    }

    if (array_key_exists('coffee', $parsed)) {
        $c = $parsed['coffee'];
        if ($c === null || $c === '') {
            $result['coffee'] = null;
        } else if (is_int($c)) {
            $result['coffee'] = $c;
        } else if (is_string($c) && preg_match('/^\d+$/', trim($c))) {
            $result['coffee'] = (int)trim($c);
        } else {
            $result['coffee'] = null;
        }
    }

    if (array_key_exists('walked', $parsed)) {
        $w = $parsed['walked'];
        if ($w === null) $result['walked'] = null;
        else $result['walked'] = (string)$w;
    }

    if (array_key_exists('meal', $parsed)) {
        $m = $parsed['meal'];
        if ($m === null) $result['meal'] = null;
        else $result['meal'] = (string)$m;
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'Method not allowed. Use POST.']);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    jsonResponse(400, ['error' => 'Invalid JSON body.']);
}

$userId = isset($input['user_id']) ? (int)$input['user_id'] : null;
$inputText = isset($input['input_text']) ? trim($input['input_text']) : null;

if (!$userId || !$inputText) {
    jsonResponse(400, ['error' => 'Missing user_id or input_text.']);
}
global $connection;
if (!($connection instanceof mysqli)) {
    jsonResponse(500, ['error' => 'Database connection not available.']);
}

try {
    $userEntryData = [
        'user_id' => $userId,
        'input_text' => $inputText
    ];
    $userEntryId = UserEntry::create($connection, $userEntryData);

    if (!$userEntryId) {
        jsonResponse(500, ['error' => 'Failed to insert user entry.']);
    }

    $aiResp = callOpenAiCurl($inputText);
    if (!$aiResp['ok']) {
        jsonResponse(200, [
            'message' => 'Saved raw entry, but failed to parse with OpenAI.',
            'user_entry_id' => $userEntryId,
            'openai_error' => $aiResp['error'] ?? 'unknown',
            'openai_raw' => $aiResp['raw'] ?? null
        ]);
    }

    $content = $aiResp['content'] ?? '';
    $parsed = extractJsonFromModelOutput($content);

    if ($parsed === null) {
        $parsedValues = [
            'user_entry_id' => $userEntryId,
            'slept' => null,
            'coffee' => null,
            'walked' => null,
            'meal' => null
        ];
        $parsedId = ParsedEntry::create($connection, $parsedValues);

        jsonResponse(200, [
            'message' => 'Saved raw entry. OpenAI did not produce valid JSON; saved empty parsed entry.',
            'user_entry_id' => $userEntryId,
            'parsed_entry_id' => $parsedId,
            'openai_content' => $content
        ]);
    }

    $normalized = normalizeParsed($parsed);
    $parsedValues = [
        'user_entry_id' => $userEntryId,
        'slept' => $normalized['slept'],
        'coffee' => $normalized['coffee'],
        'walked' => $normalized['walked'],
        'meal' => $normalized['meal']
    ];

    $parsedId = ParsedEntry::create($connection, $parsedValues);

    jsonResponse(201, [
        'message' => 'Saved entry and parsed data successfully.',
        'user_entry_id' => $userEntryId,
        'parsed_entry_id' => $parsedId,
        'parsed' => $normalized,
        'openai_raw_content' => $content
    ]);
} catch (Exception $e) {
    jsonResponse(500, ['error' => 'Server error: ' . $e->getMessage()]);
}
