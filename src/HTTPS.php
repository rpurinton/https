<?php

namespace RPurinton;

class HTTPS
{
    public static function request(array $options = []): string
    {
        $options = self::validate_options($options) or throw new HTTPSException('Invalid options provided.');
        return self::curl($options);
    }

    private static function validate_options(array $options): array
    {
        if (!isset($options['url']) || !filter_var($options['url'], FILTER_VALIDATE_URL)) {
            throw new HTTPSException('Invalid or no URL provided.');
        }

        $valid_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'];
        if (!isset($options['method']) || !in_array(strtoupper($options['method']), $valid_methods)) {
            $options['method'] = 'GET';
        }

        if (!isset($options['headers']) || !is_array($options['headers'])) {
            $options['headers'] = [];
        }

        if (!isset($options['body'])) {
            $options['body'] = '';
        } elseif (!is_string($options['body'])) {
            throw new HTTPSException('Invalid body provided. Body should be a string.');
        }

        if (!isset($options['timeout']) || !is_int($options['timeout'])) {
            $options['timeout'] = 10;
        }

        if (!isset($options['verify']) || !is_bool($options['verify'])) {
            $options['verify'] = true;
        }

        return $options;
    }

    private static function curl(array $options): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $options['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $options['method'],
            CURLOPT_POSTFIELDS => $options['body'],
            CURLOPT_HTTPHEADER => $options['headers'],
            CURLOPT_SSL_VERIFYHOST => $options['verify'] ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $options['verify'],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new HTTPSException('cURL error: ' . curl_error($curl));
        }

        curl_close($curl);
        return $response;
    }
}
