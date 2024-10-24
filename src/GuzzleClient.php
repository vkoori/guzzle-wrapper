<?php

namespace Vkoori\GuzzleWrapper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

class GuzzleClient
{
    private ?string $baseUri = null;
    private ?string $endpoint = null;
    private float $connectTimeout = 2.0;
    private float $timeout = 5.0;
    private ?string $proxy = null;
    private string $method = 'GET';
    private array $headers = [];
    private ?array $data = null;
    private array $files = [];
    private ?string $bodyFormat = null;
    private int $retries = 0;
    private int $usleep = 100000;

    /**
     * Set the base URL for the API client (optional).
     */
    public function setBaseUrl(?string $baseUri): self
    {
        $this->baseUri = $baseUri ? rtrim($baseUri, '/') . '/' : null;
        return $this;
    }

    /**
     * Set the API endpoint.
     */
    public function endpoint(?string $endpoint): self
    {
        $this->endpoint = $endpoint ? ltrim($endpoint, '/') : null;
        return $this;
    }

    /**
     * Set the connection timeout (in seconds).
     */
    public function connectTimeout(float $connectTimeout): self
    {
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    /**
     * Set the request timeout (in seconds).
     */
    public function timeout(float $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set the proxy for the API client.
     */
    public function setProxy(?string $proxy): self
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Add or merge custom headers.
     */
    public function headers(array $headers): self
    {
        unset($headers['Content-Type']);
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set the Accept header for the request.
     */
    public function accept(string $accept): self
    {
        return $this->headers(['Accept' => $accept]);
    }

    /**
     * Set the Authorization header with default Bearer token.
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->headers(['Authorization' => trim($type . ' ' . $token)]);
    }

    /**
     * Set the User Agent header for the request.
     */
    public function userAgent(string $userAgent): self
    {
        return $this->headers(['User-Agent' => $userAgent]);
    }

    /**
     * Set request data (query or body data).
     */
    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Attach files to the request.
     */
    public function attach($name, $contents = '', $filename = null, array $headers = []): self
    {
        if (is_array($name)) {
            foreach ($name as $file) {
                $this->attach(...$file);
            }
            return $this;
        }

        $this->asMultipart();

        $this->files[] = array_filter([
            'name' => $name,
            'contents' => $contents,
            'headers' => $headers,
            'filename' => $filename,
        ]);

        return $this;
    }

    /**
     * Set the request to use JSON format.
     */
    public function asJson(): self
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * Set the request to use form-urlencoded format.
     */
    public function asForm(): self
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Set the request to use multipart format.
     */
    public function asMultipart(): self
    {
        return $this->bodyFormat('multipart')->contentType('multipart/form-data');
    }

    /**
     * Set the number of retries for the request.
     */
    public function retry(int $retries, int $usleep = 100000): self
    {
        $this->retries = $retries;
        $this->usleep = $usleep;
        return $this;
    }

    /**
     * Send a HEAD request.
     */
    public function head(): ResponseInterface
    {
        return $this->method('HEAD')->send();
    }

    /**
     * Send a GET request.
     */
    public function get(): ResponseInterface
    {
        return $this->method('GET')->send();
    }

    /**
     * Send a POST request.
     */
    public function post(): ResponseInterface
    {
        return $this->method('POST')->send();
    }

    /**
     * Send a PUT request.
     */
    public function put(): ResponseInterface
    {
        return $this->method('PUT')->send();
    }

    /**
     * Send a PATCH request.
     */
    public function patch(): ResponseInterface
    {
        return $this->method('PATCH')->send();
    }

    /**
     * Send a DELETE request.
     */
    public function delete(): ResponseInterface
    {
        return $this->method('DELETE')->send();
    }

    /**
     * Set the Content-Type directly as a header.
     */
    protected function contentType(string $contentType): self
    {
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    /**
     * Set the HTTP method (GET, POST, etc.)
     */
    protected function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Set the body format type.
     */
    protected function bodyFormat(string $format): self
    {
        $this->bodyFormat = $format;
        return $this;
    }

    /**
     * Send the request.
     */
    protected function send(): ResponseInterface
    {
        // Set Guzzle options
        $options = [
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'headers'         => $this->headers,
        ];
        if ($this->baseUri) {
            $options['base_uri'] = $this->baseUri;
        }
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }

        // Initialize Guzzle client
        $client = new Client($options);

        // Handle GET request
        if ($this->method === 'GET') {
            if ($this->data) {
                $options['query'] = $this->data;
            }
        } else {
            // Handle different body formats for non-GET requests
            if ($this->bodyFormat === 'multipart') {
                $options['multipart'] = $this->files;
                if ($this->data) {
                    foreach ($this->data as $key => $value) {
                        $options['multipart'][] = [
                            'name' => $key,
                            'contents' => $value,
                        ];
                    }
                }
            } elseif ($this->bodyFormat === 'json') {
                $options['json'] = $this->data;
            } elseif ($this->bodyFormat === 'form_params') {
                $options['form_params'] = $this->data;
            } else {
                $options['form_params'] = $this->data;
            }
        }

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            try {
                return $client->request($this->method, $this->endpoint, $options);
            } catch (ClientException|ServerException|RequestException $e) {
                if ($e->hasResponse()) {
                    return $e->getResponse();
                }
                throw $e;
            } catch (ConnectException|\Throwable $e) {
                if ($attempt >= $this->retries) {
                    throw $e;
                }
                // Exponential backoff
                usleep($this->usleep * pow(2, $attempt));
            }
        }

        throw new \LogicException("Unreachable code reached: retries exhausted.");
    }
}
