<?php
declare(strict_types=1);

namespace Core;

final class Validator
{
    private array $errors = [];
    public function __construct(private array $data) {}
    public function required(string $field): self { if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') $this->errors[$field][] = 'Ce champ est obligatoire.'; return $this; }
    public function email(string $field): self { if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) $this->errors[$field][] = 'Adresse e-mail invalide.'; return $this; }
    public function integer(string $field, int $min = 1): self { if (isset($this->data[$field]) && (!filter_var($this->data[$field], FILTER_VALIDATE_INT) || (int)$this->data[$field] < $min)) $this->errors[$field][] = "La valeur doit être un entier supérieur ou égal à {$min}."; return $this; }
    public function in(string $field, array $allowed): self { if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) $this->errors[$field][] = 'Valeur non autorisée.'; return $this; }
    public function phone(string $field): self { if (isset($this->data[$field]) && !preg_match('/^[0-9+()\-\s]{7,25}$/', (string)$this->data[$field])) $this->errors[$field][] = 'Numéro de téléphone invalide.'; return $this; }
    public function fails(): bool { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
    public function validated(): array { if ($this->fails()) Response::error('Données invalides.', 422, $this->errors); return $this->data; }
}
