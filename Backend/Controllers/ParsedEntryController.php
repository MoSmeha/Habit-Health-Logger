<?php
//ParsedEntry Controller
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../db/db.php';
require __DIR__ . '/../models/ParsedEntry.php';
require __DIR__ . '/../Services/ResponseService.php';

class ParsedEntryController
{
    public function list()
    {
        global $connection;

        $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
        if ($userId === 0) {
            echo ResponseService::response(400, "Missing user_id");
            return;
        }

        $entries = ParsedEntry::findByUserId($connection, $userId);

        $out = [];
        foreach ($entries as $e) {
            $out[] = $e->toArray();
        }

        echo ResponseService::response(200, $out);
    }

    public function get()
    {
        global $connection;

        $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
        if ($id === 0) {
            echo ResponseService::response(400, "Missing id");
            return;
        }

        $entry = ParsedEntry::find($connection, $id);
        if (!$entry) {
            echo ResponseService::response(404, "Parsed entry not found");
            return;
        }

        echo ResponseService::response(200, $entry->toArray());
    }

    public function update()
    {
        global $connection;

        $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
        if ($id === 0) {
            echo ResponseService::response(400, "Missing id");
            return;
        }

        $entry = ParsedEntry::find($connection, $id);
        if (!$entry) {
            echo ResponseService::response(404, "Parsed entry not found");
            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        $data = [];

        foreach (["slept", "coffee", "walked", "meal"] as $field) {
            if (isset($input[$field])) {
                $data[$field] = $input[$field];
            }
        }

        if (empty($data)) {
            echo ResponseService::response(400, "Nothing to update");
            return;
        }

        ParsedEntry::update($connection, $id, $data);

        echo ResponseService::response(200, "Parsed entry updated");
    }

    public function delete()
    {
        global $connection;

        $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
        if ($id === 0) {
            echo ResponseService::response(400, "Missing id");
            return;
        }

        $entry = ParsedEntry::find($connection, $id);
        if (!$entry) {
            echo ResponseService::response(404, "Parsed entry not found");
            return;
        }

        ParsedEntry::delete($connection, $id);

        echo ResponseService::response(200, "Parsed entry deleted");
    }
}

// Minimal router
$controller = new ParsedEntryController();

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        if (isset($_GET["id"])) $controller->get();
        else $controller->list();
        break;

    case "PATCH":
        $controller->update();
        break;

    case "DELETE":
        $controller->delete();
        break;

    default:
        echo ResponseService::response(405, "Method Not Allowed");
}
