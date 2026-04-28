<?php

class Validator {
    private $data = [];
    private $rules = [];
    private $errors = [];
    private $messages = [];

    public function validate($data, $rules) {
        $this->data = $data;
        $this->rules = $rules;
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            
            foreach ($ruleList as $rule) {
                if ($this->validateField($field, $rule) === false) {
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    private function validateField($field, $rule) {
        $value = $this->data[$field] ?? null;
        list($ruleName, $ruleParam) = $this->parseRule($rule);

        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'integer':
                if (!empty($value) && !is_int($value) && !ctype_digit($value)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < $ruleParam) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > $ruleParam) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'in':
                if (!empty($value) && !in_array($value, explode(',', $ruleParam))) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'matches':
                if (!empty($value) && $value !== ($this->data[$ruleParam] ?? null)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;

            case 'regex':
                if (!empty($value) && !preg_match($ruleParam, $value)) {
                    $this->addError($field, $ruleName, $ruleParam);
                    return false;
                }
                break;
        }

        return true;
    }

    private function parseRule($rule) {
        if (strpos($rule, ':') !== false) {
            list($name, $param) = explode(':', $rule, 2);
            return [trim($name), trim($param)];
        }
        return [$rule, null];
    }

    private function addError($field, $rule, $param = null) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $this->getMessage($field, $rule, $param);
    }

    private function getMessage($field, $rule, $param = null) {
        if (isset($this->messages[$field][$rule])) {
            return $this->messages[$field][$rule];
        }

        $messages = [
            'required' => "$field is required",
            'email' => "$field must be a valid email",
            'url' => "$field must be a valid URL",
            'numeric' => "$field must be numeric",
            'integer' => "$field must be an integer",
            'min' => "$field must be at least $param characters",
            'max' => "$field must not exceed $param characters",
            'in' => "$field must be one of: $param",
            'matches' => "$field must match $param",
            'regex' => "$field format is invalid",
        ];

        return $messages[$rule] ?? "Validation failed for $field";
    }

    public function getErrors() {
        return $this->errors;
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function passes() {
        return empty($this->errors);
    }

    public function getFirstError($field = null) {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    public function setMessage($field, $rule, $message) {
        if (!isset($this->messages[$field])) {
            $this->messages[$field] = [];
        }
        $this->messages[$field][$rule] = $message;
    }
}

?>
