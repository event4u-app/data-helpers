# TypeScript Generation

Learn how to automatically generate TypeScript types from your DTOs.

---

## 🎯 Overview

SimpleDTO can automatically generate TypeScript interfaces and types from your PHP DTOs:

- ✅ **Automatic Generation** - Generate TypeScript from PHP
- ✅ **Type Mapping** - PHP types → TypeScript types
- ✅ **Nested DTOs** - Automatic nested type generation
- ✅ **Enums** - PHP enums → TypeScript enums
- ✅ **Arrays & Collections** - Proper array typing
- ✅ **Optional Properties** - Nullable → optional
- ✅ **JSDoc Comments** - Include PHP docblocks

---

## 🚀 Quick Start

### Laravel

```bash
php artisan dto:typescript
```

### Symfony

```bash
bin/console dto:typescript
```

### Plain PHP

```php
use event4u\DataHelpers\SimpleDTO\TypeScript\Generator;

$generator = new Generator();
$generator->generate(
    dtoPath: __DIR__ . '/src/DTO',
    outputPath: __DIR__ . '/frontend/types'
);
```

---

## 📝 Basic Example

### PHP DTO

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface UserDTO {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
}
```

---

## 🎨 Type Mapping

### Primitive Types

```php
// PHP
public readonly string $name;
public readonly int $age;
public readonly float $price;
public readonly bool $active;

// TypeScript
name: string;
age: number;
price: number;
active: boolean;
```

### Arrays

```php
// PHP
public readonly array $tags;
/** @var string[] */
public readonly array $categories;

// TypeScript
tags: any[];
categories: string[];
```

### Nullable Types

```php
// PHP
public readonly ?string $middleName = null;
public readonly ?int $age = null;

// TypeScript
middleName?: string | null;
age?: number | null;
```

### Union Types

```php
// PHP
public readonly string|int $id;

// TypeScript
id: string | number;
```

---

## 🔄 Nested DTOs

### PHP DTOs

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface AddressDTO {
  street: string;
  city: string;
  country: string;
}

export interface UserDTO {
  id: number;
  name: string;
  address: AddressDTO;
}
```

---

## 📦 Collections

### PHP DTO

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        /** @var CommentDTO[] */
        public readonly array $comments,
    ) {}
}

class CommentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface CommentDTO {
  id: number;
  content: string;
}

export interface PostDTO {
  id: number;
  title: string;
  comments: CommentDTO[];
}
```

---

## 🎯 Enums

### PHP Enum

```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly UserRole $role,
    ) {}
}
```

### Generated TypeScript

```typescript
export enum UserRole {
  ADMIN = 'admin',
  USER = 'user',
  GUEST = 'guest',
}

export interface UserDTO {
  id: number;
  name: string;
  role: UserRole;
}
```

---

## 🛠️ Configuration

### Laravel Configuration

```php
// config/simple-dto.php

return [
    'typescript' => [
        'output_path' => resource_path('js/types'),
        'namespace' => 'App\\DTO',
        'include_comments' => true,
        'export_type' => 'interface', // or 'type'
        'file_extension' => '.ts',
        'indent' => '  ', // 2 spaces
    ],
];
```

### Symfony Configuration

```yaml
# config/packages/simple_dto.yaml

simple_dto:
  typescript:
    output_path: '%kernel.project_dir%/assets/types'
    namespace: 'App\DTO'
    include_comments: true
    export_type: 'interface'
    file_extension: '.ts'
    indent: '  '
```

---

## 🎨 Advanced Features

### JSDoc Comments

```php
/**
 * User data transfer object
 * 
 * @property int $id User ID
 * @property string $name User name
 */
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

Generated TypeScript:

```typescript
/**
 * User data transfer object
 */
export interface UserDTO {
  /** User ID */
  id: number;
  /** User name */
  name: string;
}
```

### Custom Type Mapping

```php
// Register custom type mapping
$generator->registerTypeMapping(
    phpType: Carbon::class,
    tsType: 'Date'
);

// PHP
public readonly Carbon $createdAt;

// TypeScript
createdAt: Date;
```

### Exclude Properties

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[Hidden]
        public readonly string $password,
    ) {}
}
```

Generated TypeScript (password excluded):

```typescript
export interface UserDTO {
  id: number;
  name: string;
}
```

---

## 🎯 Real-World Examples

### Example 1: API Response Types

```php
class PaginatedResponseDTO extends SimpleDTO
{
    public function __construct(
        /** @var UserDTO[] */
        public readonly array $data,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
    ) {}
}
```

Generated TypeScript:

```typescript
export interface PaginatedResponseDTO {
  data: UserDTO[];
  currentPage: number;
  perPage: number;
  total: number;
  lastPage: number;
}
```

### Example 2: Form Data

```php
class CreateUserFormDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $phone = null,
        public readonly bool $acceptTerms = false,
    ) {}
}
```

Generated TypeScript:

```typescript
export interface CreateUserFormDTO {
  name: string;
  email: string;
  password: string;
  phone?: string | null;
  acceptTerms: boolean;
}
```

### Example 3: Complex Nested Structure

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly CustomerDTO $customer,
        /** @var OrderItemDTO[] */
        public readonly array $items,
        public readonly ShippingAddressDTO $shippingAddress,
        public readonly float $total,
        public readonly OrderStatus $status,
    ) {}
}
```

Generated TypeScript:

```typescript
export interface OrderDTO {
  id: number;
  customer: CustomerDTO;
  items: OrderItemDTO[];
  shippingAddress: ShippingAddressDTO;
  total: number;
  status: OrderStatus;
}
```

---

## 🔄 Watch Mode

### Laravel

```bash
php artisan dto:typescript --watch
```

### Symfony

```bash
bin/console dto:typescript --watch
```

Automatically regenerates TypeScript when PHP DTOs change.

---

## 📦 Integration with Frontend

### Vue 3 + TypeScript

```typescript
import { UserDTO } from '@/types/UserDTO';
import { ref } from 'vue';

const user = ref<UserDTO | null>(null);

async function fetchUser(id: number) {
  const response = await fetch(`/api/users/${id}`);
  user.value = await response.json() as UserDTO;
}
```

### React + TypeScript

```typescript
import { UserDTO } from './types/UserDTO';
import { useState, useEffect } from 'react';

function UserProfile({ userId }: { userId: number }) {
  const [user, setUser] = useState<UserDTO | null>(null);
  
  useEffect(() => {
    fetch(`/api/users/${userId}`)
      .then(res => res.json())
      .then((data: UserDTO) => setUser(data));
  }, [userId]);
  
  return <div>{user?.name}</div>;
}
```

### Angular + TypeScript

```typescript
import { UserDTO } from './types/UserDTO';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable()
export class UserService {
  constructor(private http: HttpClient) {}
  
  getUser(id: number): Observable<UserDTO> {
    return this.http.get<UserDTO>(`/api/users/${id}`);
  }
}
```

---

## 🎨 Output Formats

### Interface (Default)

```typescript
export interface UserDTO {
  id: number;
  name: string;
}
```

### Type Alias

```typescript
export type UserDTO = {
  id: number;
  name: string;
};
```

### Class

```typescript
export class UserDTO {
  id: number;
  name: string;
  
  constructor(data: Partial<UserDTO>) {
    Object.assign(this, data);
  }
}
```

---

## 💡 Best Practices

### 1. Use Specific Array Types

```php
// ✅ Good - specific type
/** @var UserDTO[] */
public readonly array $users

// ❌ Bad - generic array
public readonly array $users
```

### 2. Document Complex Types

```php
// ✅ Good - documented
/**
 * @var array<string, mixed>
 */
public readonly array $metadata

// ❌ Bad - undocumented
public readonly array $metadata
```

### 3. Use Enums for Constants

```php
// ✅ Good - enum
public readonly UserRole $role

// ❌ Bad - string
public readonly string $role
```

### 4. Generate Regularly

```bash
# Add to CI/CD pipeline
php artisan dto:typescript --check
```

### 5. Version Control Generated Files

```gitignore
# ❌ Don't ignore
# /resources/js/types

# ✅ Commit generated files
```

---

## 🔧 CLI Options

### Laravel

```bash
# Generate to default path
php artisan dto:typescript

# Custom output path
php artisan dto:typescript --output=resources/js/types

# Watch mode
php artisan dto:typescript --watch

# Check if types are up to date
php artisan dto:typescript --check

# Specific namespace
php artisan dto:typescript --namespace=App\\DTO\\Api
```

### Symfony

```bash
# Generate to default path
bin/console dto:typescript

# Custom output path
bin/console dto:typescript --output=assets/types

# Watch mode
bin/console dto:typescript --watch

# Check if types are up to date
bin/console dto:typescript --check

# Specific namespace
bin/console dto:typescript --namespace=App\\DTO\\Api
```

---

## 📚 Next Steps

1. [IDE Support](24-ide-support.md) - IDE integration
2. [Artisan Commands](25-artisan-commands.md) - All Laravel commands
3. [Console Commands](26-console-commands.md) - All Symfony commands
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [Security & Visibility](22-security-visibility.md)  
**Next:** [IDE Support](24-ide-support.md)

