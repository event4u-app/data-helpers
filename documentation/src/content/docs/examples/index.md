---
title: Examples Overview
description: Real-world examples and use cases
---

Real-world examples and use cases for Data Helpers.

## Overview

Browse practical examples covering common use cases:

- ✅ **API Integration** - External APIs, webhooks, REST clients
- ✅ **Form Processing** - Contact forms, registration, file uploads
- ✅ **Database Operations** - CRUD, relationships, migrations
- ✅ **File Upload** - Images, documents, validation
- ✅ **Real-World Apps** - E-commerce, blog, SaaS

## Quick Examples

### API Integration

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user.name')]
        public readonly string $name,

        #[MapFrom('user.email')]
        public readonly string $email,
    ) {}
}

$response = Http::get('https://api.example.com/users/1');
$dto = UserDTO::fromArray($response->json());
```

### Form Processing

```php
class ContactFormDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(10)]
        public readonly string $message,
    ) {}
}

$dto = ContactFormDTO::validateAndCreate($_POST);
```

### Database Operations

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// From model
$dto = UserDTO::fromModel($user);

// To model
$user = $dto->toModel(User::class);
```

## Browse Examples

- [API Integration](/examples/api-integration/) - External APIs, webhooks
- [Form Processing](/examples/form-processing/) - Contact forms, registration
- [Database Operations](/examples/database-operations/) - CRUD, relationships
- [File Upload](/examples/file-upload/) - Images, documents
- [Real-World Apps](/examples/real-world/) - Complete applications

## Example Repository

All examples are available in the repository:

```bash
git clone https://github.com/event4u-app/data-helpers.git
cd data-helpers/examples
```

Run examples:

```bash
php examples/01-basic-accessor.php
php examples/62-api-integration.php
php examples/78-real-world-ecommerce.php
```

## See Also

- [Getting Started](/getting-started/quick-start/) - Quick start guide
- [SimpleDTO Introduction](/simple-dto/introduction/) - DTO basics
- [Framework Integration](/framework-integration/overview/) - Framework guides
