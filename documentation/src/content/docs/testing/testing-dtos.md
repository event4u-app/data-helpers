---
title: Testing DTOs
description: Complete guide to testing SimpleDTO classes
---

Complete guide to testing SimpleDTO classes.

## Overview

Testing DTOs ensures data integrity and validation:

- **Unit Tests** - Test DTO creation and methods
- **Validation Tests** - Test validation rules
- **Integration Tests** - Test with frameworks
- **Feature Tests** - Test in controllers
- **Performance Tests** - Test performance

## Unit Tests

### Basic DTO Creation

```php
use PHPUnit\Framework\TestCase;

class UserDTOTest extends TestCase
{
    public function test_creates_dto_from_array(): void
    {
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $this->assertEquals(1, $dto->id);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
    }
    
    public function test_serializes_to_array(): void
    {
        $dto = new UserDTO(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );
        
        $array = $dto->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertEquals(1, $array['id']);
    }
    
    public function test_serializes_to_json(): void
    {
        $dto = new UserDTO(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );
        
        $json = $dto->toJson();
        $decoded = json_decode($json, true);
        
        $this->assertIsString($json);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('John Doe', $decoded['name']);
    }
}
```

## Validation Tests

### Test Required Fields

```php
class CreateUserDTOTest extends TestCase
{
    public function test_validates_required_fields(): void
    {
        $this->expectException(ValidationException::class);
        
        CreateUserDTO::validateAndCreate([
            'name' => 'John Doe',
            // email is missing
        ]);
    }
    
    public function test_validates_email_format(): void
    {
        $this->expectException(ValidationException::class);
        
        CreateUserDTO::validateAndCreate([
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123',
        ]);
    }
    
    public function test_validates_password_strength(): void
    {
        $this->expectException(ValidationException::class);
        
        CreateUserDTO::validateAndCreate([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak', // Too short
        ]);
    }
    
    public function test_creates_valid_dto(): void
    {
        $dto = CreateUserDTO::validateAndCreate([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]);
        
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
    }
}
```

## Conditional Property Tests

### Test Visibility

```php
class UserDTOTest extends TestCase
{
    public function test_hides_email_when_not_authenticated(): void
    {
        Auth::logout();
        
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $array = $dto->toArray();
        
        $this->assertArrayNotHasKey('email', $array);
    }
    
    public function test_shows_email_when_authenticated(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $array = $dto->toArray();
        
        $this->assertArrayHasKey('email', $array);
        $this->assertEquals('john@example.com', $array['email']);
    }
    
    public function test_shows_admin_data_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);
        
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'adminData' => ['key' => 'value'],
        ]);
        
        $array = $dto->toArray();
        
        $this->assertArrayHasKey('adminData', $array);
    }
}
```

## Type Casting Tests

### Test Casts

```php
class UserDTOTest extends TestCase
{
    public function test_casts_date_to_carbon(): void
    {
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'createdAt' => '2024-01-15 10:30:00',
        ]);
        
        $this->assertInstanceOf(Carbon::class, $dto->createdAt);
        $this->assertEquals('2024-01-15', $dto->createdAt->format('Y-m-d'));
    }
    
    public function test_casts_enum(): void
    {
        $dto = UserDTO::fromArray([
            'id' => 1,
            'name' => 'John Doe',
            'role' => 'admin',
        ]);
        
        $this->assertInstanceOf(UserRole::class, $dto->role);
        $this->assertEquals(UserRole::ADMIN, $dto->role);
    }
}
```

## Integration Tests

### Laravel Controller Tests

```php
class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_user_with_dto(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]);
        
        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }
    
    public function test_validates_user_input(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
```

### Symfony Controller Tests

```php
class UserControllerTest extends WebTestCase
{
    public function testCreateUser(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }
}
```

## Performance Tests

### Benchmark DTO Creation

```php
class UserDTOPerformanceTest extends TestCase
{
    public function test_creates_1000_dtos_quickly(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            UserDTO::fromArray([
                'id' => $i,
                'name' => "User $i",
                'email' => "user$i@example.com",
            ]);
        }
        
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(1.0, $duration, 'Creating 1000 DTOs should take less than 1 second');
    }
}
```

## Best Practices

### 1. Test All Validation Rules

```php
// ✅ Good - Test each validation rule
public function test_validates_email_format(): void { ... }
public function test_validates_password_length(): void { ... }
public function test_validates_required_fields(): void { ... }
```

### 2. Test Edge Cases

```php
// ✅ Good - Test edge cases
public function test_handles_null_values(): void { ... }
public function test_handles_empty_arrays(): void { ... }
public function test_handles_special_characters(): void { ... }
```

### 3. Use Data Providers

```php
/**
 * @dataProvider invalidEmailProvider
 */
public function test_validates_email_format(string $email): void
{
    $this->expectException(ValidationException::class);
    
    CreateUserDTO::validateAndCreate([
        'name' => 'John Doe',
        'email' => $email,
        'password' => 'Password123',
    ]);
}

public function invalidEmailProvider(): array
{
    return [
        ['invalid'],
        ['@example.com'],
        ['user@'],
        ['user @example.com'],
    ];
}
```

## See Also

- [Creating DTOs](/simple-dto/creating-dtos/) - DTO creation guide
- [Validation](/simple-dto/validation/) - Validation guide
- [Type Casting](/simple-dto/type-casting/) - Type casting guide

