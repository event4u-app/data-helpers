# Testing DTOs

Complete guide to testing SimpleDTO classes.

---

## 🎯 Overview

Testing DTOs ensures data integrity and validation:

- ✅ **Unit Tests** - Test DTO creation and methods
- ✅ **Validation Tests** - Test validation rules
- ✅ **Integration Tests** - Test with frameworks
- ✅ **Feature Tests** - Test in controllers
- ✅ **Performance Tests** - Test performance

---

## 🧪 Unit Tests

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

---

## ✅ Validation Tests

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

---

## 🎨 Conditional Property Tests

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

---

## 🔄 Type Casting Tests

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
        $this->assertEquals(UserRole::Admin, $dto->role);
    }
}
```

---

## 🧮 Computed Property Tests

### Test Computed Values

```php
class OrderDTOTest extends TestCase
{
    public function test_computes_total(): void
    {
        $dto = new OrderDTO(
            id: 1,
            items: [
                new OrderItemDTO(productId: 1, quantity: 2, price: 10.00),
                new OrderItemDTO(productId: 2, quantity: 1, price: 15.00),
            ],
        );
        
        $this->assertEquals(35.00, $dto->total());
    }
    
    public function test_computes_discount(): void
    {
        $dto = new OrderDTO(
            id: 1,
            items: [
                new OrderItemDTO(productId: 1, quantity: 2, price: 10.00),
            ],
            couponCode: 'SAVE10',
        );
        
        $this->assertEquals(2.00, $dto->discount()); // 10% of 20.00
    }
}
```

---

## 🔌 Integration Tests (Laravel)

### Test with Eloquent

```php
class UserDTOIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_dto_from_model(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $dto = UserDTO::fromModel($user);
        
        $this->assertEquals($user->id, $dto->id);
        $this->assertEquals($user->name, $dto->name);
        $this->assertEquals($user->email, $dto->email);
    }
    
    public function test_creates_model_from_dto(): void
    {
        $dto = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'Password123',
        );
        
        $user = User::create($dto->toArray());
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }
}
```

---

## 🎯 Feature Tests (Laravel)

### Test in Controllers

```php
class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_user(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }
    
    public function test_validates_user_creation(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            // email is missing
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    
    public function test_returns_user_resource(): void
    {
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
            ]);
    }
}
```

---

## 🚀 Performance Tests

### Test Performance

```php
class UserDTOPerformanceTest extends TestCase
{
    public function test_creates_many_dtos_quickly(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 10000; $i++) {
            UserDTO::fromArray([
                'id' => $i,
                'name' => "User $i",
                'email' => "user$i@example.com",
            ]);
        }
        
        $duration = microtime(true) - $start;
        
        // Should create 10k DTOs in less than 1 second
        $this->assertLessThan(1.0, $duration);
    }
    
    public function test_validation_caching_improves_performance(): void
    {
        // Without cache
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            CreateUserDTO::validateAndCreate([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'Password123',
            ]);
        }
        $uncachedDuration = microtime(true) - $start;
        
        // With cache
        Artisan::call('dto:cache');
        
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            CreateUserDTO::validateAndCreate([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'Password123',
            ]);
        }
        $cachedDuration = microtime(true) - $start;
        
        // Cached should be significantly faster
        $this->assertLessThan($uncachedDuration / 10, $cachedDuration);
    }
}
```

---

## 💡 Best Practices

### 1. Test All Validation Rules

```php
public function test_validates_all_rules(): void
{
    // Test required
    $this->expectException(ValidationException::class);
    CreateUserDTO::validateAndCreate(['name' => 'John']);
    
    // Test email format
    $this->expectException(ValidationException::class);
    CreateUserDTO::validateAndCreate([
        'name' => 'John',
        'email' => 'invalid',
    ]);
    
    // Test password strength
    $this->expectException(ValidationException::class);
    CreateUserDTO::validateAndCreate([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'weak',
    ]);
}
```

### 2. Test Conditional Properties

```php
public function test_conditional_properties(): void
{
    // Test when condition is false
    Auth::logout();
    $dto = UserDTO::fromArray(['email' => 'john@example.com']);
    $this->assertArrayNotHasKey('email', $dto->toArray());
    
    // Test when condition is true
    Auth::login($user);
    $dto = UserDTO::fromArray(['email' => 'john@example.com']);
    $this->assertArrayHasKey('email', $dto->toArray());
}
```

### 3. Test Edge Cases

```php
public function test_handles_null_values(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John',
        'bio' => null,
    ]);
    
    $this->assertNull($dto->bio);
}

public function test_handles_empty_arrays(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John',
        'tags' => [],
    ]);
    
    $this->assertIsArray($dto->tags);
    $this->assertEmpty($dto->tags);
}
```

---

## 📚 Next Steps

1. [Best Practices](29-best-practices.md) - Best practices
2. [Performance](27-performance.md) - Performance optimization
3. [Troubleshooting](32-troubleshooting.md) - Common issues

---

**Previous:** [Form Requests](39-form-requests.md)  
**Next:** [Introduction](01-introduction.md) (Back to start)

