<?php

namespace RPurinton;

class HTTPS
{
    public static function request(array $options = []): string
    {
        if (!extension_loaded('curl')) {
            throw new HTTPSException('cURL extension is not loaded.');
        }
        $options = self::validate_options($options);
        return self::curl($options);
    }

    private static function default_options(): array
    {
        return [
            'method'          => 'GET',
            'headers'         => [],
            'body'            => '',
            'timeout'         => 10,
            'connect_timeout' => 5,
            'verify'          => true,
            'retries'         => 0, // number of retry attempts on cURL errors
        ];
    }

    private static function validate_options(array $options): array
    {
        $defaults = self::default_options();
        $options = array_merge($defaults, $options);

        // Validate URL
        if (!isset($options['url']) || !filter_var($options['url'], FILTER_VALIDATE_URL)) {
            throw new HTTPSException('Invalid or no URL provided.');
        }

        // Validate HTTP method
        $valid_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'];
        $method = strtoupper($options['method'] ?? '');
        if (!in_array($method, $valid_methods, true)) {
            $options['method'] = 'GET';
        } else {
            $options['method'] = $method;
        }

        // Validate headers: allow associative arrays and arrays of strings
        if (!is_array($options['headers'])) {
            $options['headers'] = [];
        } else {
            $normalizedHeaders = [];
            foreach ($options['headers'] as $key => $value) {
                if (is_int($key)) {
                    // Already a string header like "Content-Type: application/json"
                    if (!is_string($value)) {
                        throw new HTTPSException('Invalid header provided. Headers should be string values.');
                    }
                    $normalizedHeaders[] = $value;
                } else {
                    // key => value pair, convert to "Key: value"
                    if (!is_string($value)) {
                        throw new HTTPSException('Invalid header value for ' . $key . '. Headers should be string values.');
                    }
                    $normalizedHeaders[] = $key . ': ' . $value;
                }
            }
            $options['headers'] = $normalizedHeaders;
        }

        // Validate body
        if (!is_string($options['body'])) {
            throw new HTTPSException('Invalid body provided. Body should be a string.');
        }

        // Validate numeric options: timeout and connect_timeout must be positive integers
        if (!is_int($options['timeout']) || $options['timeout'] <= 0) {
            $options['timeout'] = $defaults['timeout'];
        }
        if (!is_int($options['connect_timeout']) || $options['connect_timeout'] <= 0) {
            $options['connect_timeout'] = $defaults['connect_timeout'];
        }

        // Validate verify flag
        if (!is_bool($options['verify'])) {
            $options['verify'] = $defaults['verify'];
        }

        // Validate retries option
        if (!isset($options['retries']) || !is_int($options['retries']) || $options['retries'] < 0) {
            $options['retries'] = $defaults['retries'];
        }

        return $options;
    }

    private static function curl(array $options): string
    {
        $attempts = 0;
        $maxAttempts = 1 + $options['retries'];
        $lastError = '';
        while ($attempts < $maxAttempts) {
            $curl = curl_init();
            if ($curl === false) {
                throw new HTTPSException('Failed to initialize cURL.');
            }

            try {
                curl_setopt_array($curl, [
                    CURLOPT_URL => $options['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => $options['timeout'],
                    CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'],
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => $options['method'],
                    CURLOPT_POSTFIELDS => $options['body'],
                    CURLOPT_HTTPHEADER => $options['headers'],
                    CURLOPT_SSL_VERIFYHOST => $options['verify'] ? 2 : 0,
                    CURLOPT_SSL_VERIFYPEER => $options['verify'],
                    CURLOPT_FAILONERROR => true,
                ]);

                $response = curl_exec($curl);
                if ($response === false) {
                    $lastError = 'cURL error on ' . $options['method'] . ' ' . $options['url'] . ': ' . curl_error($curl);
                    error_log($lastError);
                    // Retry if attempts remain
                    $attempts++;
                    continue;
                }

                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($httpCode >= 400) {
                    $lastError = 'HTTP error on ' . $options['method'] . ' ' . $options['url'] . ': Received status code ' . $httpCode;
                    error_log($lastError);
                    throw new HTTPSException($lastError);
                }

                return $response;
            } finally {
                curl_close($curl);
            }
        }
        // If all attempts fail, throw exception with last error message
        throw new HTTPSException($lastError ?: 'Unknown error occurred during cURL execution.');
    }
}