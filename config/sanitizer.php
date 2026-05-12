<?php

/**
 * Sanitizes input values to prevent XSS and other injection attacks.
 * @param mixed $input The input to sanitize (string or array)
 * @return mixed The sanitized output
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    
    if (is_string($input)) {
        // Strip tags and convert special characters to HTML entities
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return trim($input);
    }
    
    return $input;
}
