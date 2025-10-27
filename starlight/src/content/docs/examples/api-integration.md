---
title: API Integration Examples
description: Examples for integrating with external APIs
---

Examples for integrating with external APIs.

## Introduction

Common patterns for API integration:

- ✅ **REST APIs** - GET, POST, PUT, DELETE
- ✅ **Webhooks** - Handle incoming webhooks
- ✅ **Response Mapping** - Map API responses to Dtos
- ✅ **Error Handling** - Handle API errors

## REST API Client

### GET Request

```php
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

class UserDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('data.id')]
        public readonly int $id,
        
        #[MapFrom('data.attributes.name')]
        public readonly string $name,
        
        #[MapFrom('data.attributes.email')]
        public readonly string $email,
    ) {}
}

// Fetch from API
$response = Http::get('https://api.example.com/users/1');
$dto = UserDto::fromArray($response->json());
```

### POST Request

```php
class CreateUserDto extends SimpleDto
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}

$dto = CreateUserDto::fromArray($_POST);
$dto->validate();

$response = Http::post('https://api.example.com/users', $dto->toArray());
```

### PUT Request

```php
class UpdateUserDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
    ) {}
}

$dto = UpdateUserDto::fromArray($_POST);

$response = Http::put("https://api.example.com/users/{$id}", 
    array_filter($dto->toArray())
);
```

## Weather API Integration

```php
class WeatherDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('location.name')]
        public readonly string $city,
        
        #[MapFrom('current.temp_c')]
        public readonly float $temperature,
        
        #[MapFrom('current.condition.text')]
        public readonly string $condition,
        
        #[MapFrom('current.humidity')]
        public readonly int $humidity,
    ) {}
}

$response = Http::get('https://api.weatherapi.com/v1/current.json', [
    'key' => env('WEATHER_API_KEY'),
    'q' => 'London',
]);

$weather = WeatherDto::fromArray($response->json());

echo "Temperature in {$weather->city}: {$weather->temperature}°C\n";
echo "Condition: {$weather->condition}\n";
```

## GitHub API Integration

```php
class GitHubRepoDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[MapFrom('full_name')]
        public readonly string $fullName,
        
        public readonly string $description,
        
        #[MapFrom('stargazers_count')]
        public readonly int $stars,
        
        #[MapFrom('forks_count')]
        public readonly int $forks,
        
        #[MapFrom('html_url')]
        public readonly string $url,
    ) {}
}

$response = Http::get('https://api.github.com/repos/event4u-app/data-helpers');
$repo = GitHubRepoDto::fromArray($response->json());

echo "{$repo->fullName}\n";
echo "Stars: {$repo->stars}, Forks: {$repo->forks}\n";
```

## Stripe API Integration

```php
class StripeCustomerDto extends SimpleDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $created,
    ) {}
}

$response = Http::withToken(env('STRIPE_SECRET'))
    ->get('https://api.stripe.com/v1/customers/cus_123');

$customer = StripeCustomerDto::fromArray($response->json());
```

## Webhook Handling

### GitHub Webhook

```php
class GitHubWebhookDto extends SimpleDto
{
    public function __construct(
        public readonly string $action,
        
        #[MapFrom('repository.name')]
        public readonly string $repository,
        
        #[MapFrom('sender.login')]
        public readonly string $sender,
    ) {}
}

// Handle webhook
$payload = json_decode(file_get_contents('php://input'), true);
$webhook = GitHubWebhookDto::fromArray($payload);

match($webhook->action) {
    'opened' => handlePullRequestOpened($webhook),
    'closed' => handlePullRequestClosed($webhook),
    default => null,
};
```

### Stripe Webhook

```php
class StripeWebhookDto extends SimpleDto
{
    public function __construct(
        public readonly string $type,
        
        #[MapFrom('data.object')]
        public readonly array $object,
    ) {}
}

$payload = json_decode(file_get_contents('php://input'), true);
$webhook = StripeWebhookDto::fromArray($payload);

match($webhook->type) {
    'payment_intent.succeeded' => handlePaymentSuccess($webhook),
    'payment_intent.failed' => handlePaymentFailure($webhook),
    default => null,
};
```

## Pagination

```php
class PaginatedResponseDto extends SimpleDto
{
    public function __construct(
        public readonly array $data,
        
        #[MapFrom('meta.current_page')]
        public readonly int $currentPage,
        
        #[MapFrom('meta.last_page')]
        public readonly int $lastPage,
        
        #[MapFrom('meta.total')]
        public readonly int $total,
    ) {}
}

$response = Http::get('https://api.example.com/users', [
    'page' => 1,
    'per_page' => 20,
]);

$paginated = PaginatedResponseDto::fromArray($response->json());

echo "Page {$paginated->currentPage} of {$paginated->lastPage}\n";
echo "Total: {$paginated->total}\n";
```

## Error Handling

```php
class ApiErrorDto extends SimpleDto
{
    public function __construct(
        public readonly string $message,
        public readonly int $code,
        public readonly ?array $errors = null,
    ) {}
}

try {
    $response = Http::post('https://api.example.com/users', $data);
    
    if ($response->failed()) {
        $error = ApiErrorDto::fromArray($response->json());
        throw new ApiException($error->message, $error->code);
    }
    
    $user = UserDto::fromArray($response->json());
} catch (ApiException $e) {
    Log::error('API error', ['message' => $e->getMessage()]);
}
```

## Rate Limiting

```php
class RateLimitDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('X-RateLimit-Limit')]
        public readonly int $limit,
        
        #[MapFrom('X-RateLimit-Remaining')]
        public readonly int $remaining,
        
        #[MapFrom('X-RateLimit-Reset')]
        public readonly int $reset,
    ) {}
}

$response = Http::get('https://api.github.com/user');
$rateLimit = RateLimitDto::fromArray($response->headers());

if ($rateLimit->remaining < 10) {
    Log::warning('API rate limit low', [
        'remaining' => $rateLimit->remaining,
        'reset' => Carbon::createFromTimestamp($rateLimit->reset),
    ]);
}
```

## See Also

- [Property Mapping](/simple-dto/property-mapping/) - MapFrom attribute
- [Type Casting](/simple-dto/type-casting/) - Type casting
- [Validation](/simple-dto/validation/) - Validation rules

