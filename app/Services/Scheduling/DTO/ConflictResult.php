<?php

namespace App\Services\Scheduling\DTO;

class ConflictResult
{
    /** @var array<string, array<string>> */
    protected array $violations = [];

    public function addViolation(string $type, string $message): void
    {
        $this->violations[$type][] = $message;
    }

    public function hasConflicts(): bool
    {
        return ! empty($this->violations);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getAllMessages(): array
    {
        $messages = [];
        foreach ($this->violations as $typeMessages) {
            $messages = array_merge($messages, $typeMessages);
        }
        return $messages;
    }

    public static function ok(): self
    {
        return new self();
    }
}
