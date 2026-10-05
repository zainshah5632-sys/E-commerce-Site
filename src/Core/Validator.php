<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->validate();
        return $v;
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $isRequired = in_array('required', $ruleList, true);

            if (($value === null || $value === '') && !$isRequired) {
                continue;
            }

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $ruleName, $value, $params);
                if (isset($this->errors[$field])) {
                    break; // Stop at first error for this field
                }
            }
        }

        if ($this->fails()) {
            Session::instance()->set('_old_input', $this->data);
        } else {
            Session::instance()->remove('_old_input');
        }

        return $this->passes();
    }

    private function applyRule(string $field, string $rule, mixed $value, array $params): void
    {
        $label = ucwords(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->errors[$field] = "{$label} is required.";
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "Please enter a valid email address.";
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_numeric($value) && (float)$value < $min) {
                    $this->errors[$field] = "{$label} must be at least {$min}.";
                } elseif (is_string($value) && mb_strlen($value) < $min) {
                    $this->errors[$field] = "{$label} must be at least {$min} characters.";
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 0);
                if (is_numeric($value) && (float)$value > $max) {
                    $this->errors[$field] = "{$label} must not exceed {$max}.";
                } elseif (is_string($value) && mb_strlen($value) > $max) {
                    $this->errors[$field] = "{$label} must not exceed {$max} characters.";
                }
                break;

            case 'in':
                if (!in_array((string)$value, $params, true)) {
                    $this->errors[$field] = "The selected {$label} is invalid.";
                }
                break;

            case 'int':
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    $this->errors[$field] = "{$label} must be an integer.";
                }
                break;

            case 'regex':
                $pattern = $params[0] ?? '';
                if (!preg_match($pattern, (string)$value)) {
                    $this->errors[$field] = "{$label} format is invalid.";
                }
                break;

            case 'uuid':
                $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
                if (!is_string($value) || !preg_match($uuidRegex, $value)) {
                    $this->errors[$field] = "{$label} is not a valid identifier.";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    $this->errors[$field] = "{$label} confirmation does not match.";
                }
                break;

            case 'alpha_spaces':
                if (!is_string($value) || !preg_match('/^[a-zA-Z\s\-\'\.]+$/u', $value)) {
                    $this->errors[$field] = "{$label} must contain only alphabets (no numbers or special characters).";
                }
                break;

            case 'username':
                if (!is_string($value) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $value)) {
                    $this->errors[$field] = "Username must be between 3 and 30 characters and contain only letters, numbers, and underscores.";
                }
                break;

            case 'complex_password':
                $pass = (string)$value;
                $hasLetter = preg_match('/[a-zA-Z]/', $pass);
                $hasDigit  = preg_match('/[0-9]/', $pass);
                $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $pass);
                if (!$hasLetter || !$hasDigit || !$hasSymbol) {
                    $this->errors[$field] = "Password must include at least one letter, one number, and one symbol/sign.";
                }
                break;

            case 'phone_11':
                $cleaned = preg_replace('/[^0-9]/', '', (string)$value);
                if (strlen($cleaned) !== 11) {
                    $this->errors[$field] = "Phone number must be exactly 11 digits.";
                }
                break;
        }
    }

    public function passes(): bool
    {
        return count($this->errors) === 0;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        if (count($this->errors) === 0) {
            return null;
        }
        return reset($this->errors);
    }
}
