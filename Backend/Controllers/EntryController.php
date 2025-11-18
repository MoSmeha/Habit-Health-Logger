<?php
//Entry Controller
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/Entry.php';
require __DIR__ . '/../models/ParsedEntry.php';
require __DIR__ . '/../Services/AiParser.php';
require __DIR__ . '/../Services/ResponseService.php';

class UserEntryController
{
    public function parseOnly()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $text = $input['input_text'] ?? null;

        if (!$text) {
            echo ResponseService::response(400, "Missing input_text");
            return;
        }

        try {
            // Ask AI to parse
            $aiResp = AiParser::callOpenAi($text);

            if (!$aiResp['ok']) {
                echo ResponseService::response(400, [
                    "error" => $aiResp["error"]
                ]);
                return;
            }

            $parsed = AiParser::extract($aiResp["content"]);

            if (!$parsed) {
                echo ResponseService::response(200, [
                    "parsed" => [
                        "slept" => null,
                        "coffee" => null,
                        "walked" => null,
                        "meal" => null
                    ]
                ]);
                return;
            }

            // Normalize and return (don't save yet)
            $normalized = AiParser::normalize($parsed);

            echo ResponseService::response(200, [
                "parsed" => $normalized
            ]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }

    public function create()
    {
        global $connection;
        $input = json_decode(file_get_contents("php://input"), true);

        $userId = $input['user_id'] ?? null;
        $text = $input['input_text'] ?? null;
        $slept = $input['slept'] ?? null;
        $coffee = $input['coffee'] ?? null;
        $walked = $input['walked'] ?? null;
        $meal = $input['meal'] ?? null;

        if (!$userId || !$text) {
            echo ResponseService::response(400, "Missing user_id or input_text");
            return;
        }

        try {
            //Save raw user entry
            $userEntryId = UserEntry::create($connection, [
                "user_id" => (int)$userId,
                "input_text" => $text,
                "created_at" => date("Y-m-d H:i:s"),
            ]);

            if (!$userEntryId) {
                echo ResponseService::response(500, "Failed to insert user entry");
                return;
            }

            // Save the parsed entry (from frontend)
            $parsedId = ParsedEntry::create($connection, [
                "user_entry_id" => $userEntryId,
                "slept" => $slept,
                "coffee" => $coffee ? (int)$coffee : null,
                "walked" => $walked,
                "meal" => $meal,
            ]);

            echo ResponseService::response(201, [
                "user_entry_id" => $userEntryId,
                "parsed_entry_id" => $parsedId
            ]);
        } catch (Exception $e) {
            echo ResponseService::response(500, "Server error: " . $e->getMessage());
        }
    }
}

$controller = new UserEntryController();
$action = $_GET['action'] ?? 'create';

switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":
        // i pass this in frotnend (fetch)
        if ($action === 'parse') {
            $controller->parseOnly();
        } else {
            $controller->create();
        }
        break;
    default:
        echo ResponseService::response(405, "Method Not Allowed");
}
