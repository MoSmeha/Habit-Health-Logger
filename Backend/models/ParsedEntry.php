<?php

require_once __DIR__ . '/Model.php';

class ParsedEntry extends Model
{
    private int $id;
    private int $user_entry_id;
    private ?string $slept;
    private ?int $coffee;
    private ?string $walked;
    private ?string $meal;
    private string $created_at;

    protected static string $table = "parsed_entries";

    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->user_entry_id = $data["user_entry_id"];
        $this->slept = $data["slept"] ?? null;
        $this->coffee = $data["coffee"] ?? null;
        $this->walked = $data["walked"] ?? null;
        $this->meal = $data["meal"] ?? null;
        $this->created_at = $data["created_at"] ?? date("Y-m-d H:i:s");
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserEntryId(): int
    {
        return $this->user_entry_id;
    }

    public function getSlept(): ?string
    {
        return $this->slept;
    }

    public function getCoffee(): ?int
    {
        return $this->coffee;
    }

    public function getWalked(): ?string
    {
        return $this->walked;
    }

    public function getMeal(): ?string
    {
        return $this->meal;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    // Setters
    public function setSlept(?string $slept)
    {
        $this->slept = $slept;
    }

    public function setCoffee(?int $coffee)
    {
        $this->coffee = $coffee;
    }

    public function setWalked(?string $walked)
    {
        $this->walked = $walked;
    }

    public function setMeal(?string $meal)
    {
        $this->meal = $meal;
    }

    // Convert object to array
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "user_entry_id" => $this->user_entry_id,
            "slept" => $this->slept,
            "coffee" => $this->coffee,
            "walked" => $this->walked,
            "meal" => $this->meal,
            "created_at" => $this->created_at
        ];
    }

    public function __toString(): string
    {
        return $this->id . " | entry:" . $this->user_entry_id .
            " | slept:" . $this->slept .
            " | coffee:" . $this->coffee .
            " | walked:" . $this->walked .
            " | meal:" . $this->meal;
    }
}
