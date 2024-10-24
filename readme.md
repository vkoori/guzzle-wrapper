A flexible PHP-based API client built on Guzzle, supporting dynamic body formats, complex query parameters, retry logic with exponential backoff, and robust exception handling.

## Install

```
composer require vkoori/guzzle-wrapper
```

##

```php
// Create a GuzzleClient instance
$client = new \Vkoori\GuzzleWrapper\GuzzleClient();

// Perform a POST request with extensive chaining
$response = $client
    ->setBaseUrl('https://api.example.com')               // Set the base URL
    ->endpoint('users')                                   // Set the endpoint (e.g., /users)
    ->headers([                                           // Set custom headers
        'X-Custom-Header' => 'CustomHeaderValue',
    ])
    ->accept('application/json')                          // Set the Accept header
    ->withToken('your-api-token-here')                    // Set Authorization header with Bearer token
    ->userAgent('MyCustomUserAgent/1.0')                  // Set a custom User-Agent header
    ->connectTimeout(2.0)                                 // Set connection timeout to 2 seconds
    ->timeout(5.0)                                        // Set overall request timeout to 5 seconds
    ->retry(3, 300000)                                    // Retry up to 3 times with increasing delay
    ->asJson()                                            // Use JSON format for the request body
    ->data([                                              // Add JSON data for the request body
        'name'     => 'John Doe',
        'email'    => 'john@example.com',
        'password' => 'secretpassword',
    ])
    ->post();                                             // Send a POST request

// Response
echo (string) $response->getBody();
```
