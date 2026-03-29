<?php

namespace App\Services\Scheduling\DTO;

class ConflictResult
{
    /** @var array<string, array<string>> */
    protected array $violations = [];
    protected array $warnings = [];

    public function addViolation(string $type, string $message): void
    {
        $this->violations[$type][] = $message;
    }

    public function addWarning(string $type, string $message): void
    {
        $this->warnings[$type][] = $message;
    }

    public function hasConflicts(): bool
    {
        return ! empty($this->violations);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
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

    public function getAllWarnings(): array
    {
        $messages = [];
        foreach ($this->warnings as $typeMessages) {
            $messages = array_merge($messages, $typeMessages);
        }
        return $messages;
    }

    public static function ok(): self
    {
        return new self();
    }
}
