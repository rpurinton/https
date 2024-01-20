<?php

namespace RPurinton\HTTPS;

use RPurinton\HTTPS\Exceptions\HTTPSException;

class HTTPSRequest
{
    private ?int $response_code = null;
    private ?array $response_headers = null;
    private ?string $response_body = null;

    public function __construct(private array $options = [])
    {
        $this->validate_options();
        $this->execute();
    }

    private function validate_options(): void
    {
        if (!isset($this->options['url']) || !filter_var($this->options['url'], FILTER_VALIDATE_URL)) {
            throw new HTTPSException('Invalid or no URL provided.');
        }

        $valid_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'];
        if (!isset($this->options['method']) || !in_array(strtoupper($this->options['method']), $valid_methods)) {
            $this->options['method'] = 'GET';
        }

        if (!isset($this->options['headers']) || !is_array($this->options['headers'])) {
            $this->options['headers'] = [];
        }

        if (!isset($this->options['body'])) {
            $this->options['body'] = '';
        } elseif (!is_string($this->options['body'])) {
            throw new HTTPSException('Invalid body provided. Body should be a string.');
        }

        if (!isset($this->options['timeout']) || !is_int($this->options['timeout'])) {
            $this->options['timeout'] = 10;
        }

        if (!isset($this->options['verify']) || !is_bool($this->options['verify'])) {
            $this->options['verify'] = true;
        }
    }

    private function execute()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->options['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $this->options['method'],
            CURLOPT_POSTFIELDS => $this->options['body'],
            CURLOPT_HTTPHEADER => $this->options['headers'],
            CURLOPT_SSL_VERIFYHOST => $this->options['verify'] ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $this->options['verify'],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new HTTPSException('cURL error: ' . curl_error($curl));
        }

        $this->response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->response_headers = curl_getinfo($curl);
        $this->response_body = $response;

        curl_close($curl);
    }

    public function __toString(): string
    {
        return $this->response_body;
    }

    public function __toArray(): array
    {
        $decoded = json_decode($this->response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HTTPSException('Error decoding JSON: ' . json_last_error_msg());
        }
        return $decoded;
    }

    public function __toObject(): object
    {
        $decoded = json_decode($this->response_body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HTTPSException('Error decoding JSON: ' . json_last_error_msg());
        }
        return $decoded;
    }

    public function getResponseHeaders(): array
    {
        return $this->response_headers;
    }

    public function getResponseBody(): string
    {
        return $this->response_body;
    }

    public function getResponseCode(): int
    {
        return $this->response_code;
    }

    public function getResponseCodeText(): string
    {
        return match ($this->response_code) {
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',

            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',

            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',

            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',

            default => 'Unknown Response Code',
        };
    }

    public function getResponseCodeClass(): string
    {
        return match (substr($this->response_code, 0, 1)) {
            1 => 'Informational',
            2 => 'Success',
            3 => 'Redirection',
            4 => 'Client Error',
            5 => 'Server Error',
            default => 'Unknown Response Code Class',
        };
    }

    public function getResponseCodeClassText(): string
    {
        return match (substr($this->response_code, 0, 1)) {
            1 => 'The request was received, continuing process',
            2 => 'The request was successfully received, understood, and accepted',
            3 => 'Further action needs to be taken in order to complete the request',
            4 => 'The request contains bad syntax or cannot be fulfilled',
            5 => 'The server failed to fulfill an apparently valid request',
            default => 'Unknown Response Code Class Text',
        };
    }

    public function getResponseCodeClassDescription(): string
    {
        return match (substr($this->response_code, 0, 1)) {
            1 => 'An informational response indicates that the request was received and understood. It is issued on a provisional basis while request processing continues. It alerts the client to wait for a final response. The message consists only of the status line and optional header fields, and is terminated by an empty line. As the HTTP/1.0 standard did not define any 1xx status codes, servers must not send a 1xx response to an HTTP/1.0 compliant client except under experimental conditions.',
            2 => 'This class of status codes indicates the action requested by the client was received, understood, and accepted.',
            3 => 'Further action needs to be taken in order to complete the request. This class of status code indicates a provisional response, consisting only of the Status-Line and optional headers, and is terminated by an empty line. Since HTTP/1.0 did not define any 1xx status codes, servers must not send a 1xx response to an HTTP/1.0 compliant client except under experimental conditions.',
            4 => 'The 4xx class of status code is intended for cases in which the client seems to have erred. Except when responding to a HEAD request, the server should include an entity containing an explanation of the error situation, and whether it is a temporary or permanent condition. These status codes are applicable to any request method. User agents should display any included entity to the user.',
            5 => 'Response status codes beginning with the digit "5" indicate cases in which the server is aware that it has encountered an error or is otherwise incapable of performing the request. Except when responding to a HEAD request, the server should include an entity containing an explanation of the error situation, and indicate whether it is a temporary or permanent condition. Likewise, user agents should display any included entity to the user. These response codes are applicable to any request method.',
            default => 'Unknown Response Code Class Description',
        };
    }

    public function getResponseCodeClassColor(): string
    {
        return match (substr($this->response_code, 0, 1)) {
            1 => 'info',
            2 => 'success',
            3 => 'warning',
            4 => 'danger',
            5 => 'danger',
            default => 'secondary',
        };
    }

    public function getResponseCodeClassIcon(): string
    {
        return match (substr($this->response_code, 0, 1)) {
            1 => 'info-circle',
            2 => 'check-circle',
            3 => 'exclamation-triangle',
            4 => 'exclamation-circle',
            5 => 'exclamation-circle',
            default => 'question-circle',
        };
    }
}
