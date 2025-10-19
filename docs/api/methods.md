# Methods API Reference

Complete API reference for all SimpleDTO methods.

---

## 📋 Table of Contents

- [SimpleDTO Class](#simpledto-class)
- [DataCollection Class](#datacollection-class)
- [Static Factory Methods](#static-factory-methods)
- [Instance Methods](#instance-methods)
- [Serialization Methods](#serialization-methods)

---

## SimpleDTO Class

**Namespace:** `event4u\DataHelpers\SimpleDTO`

**Description:** Base class for all DTOs.

---

### Static Factory Methods

#### fromArray()

**Signature:**
```php
public static function fromArray(array $data): static
```

**Description:** Create DTO instance from array.

**Parameters:**
- `$data` (array): Input data

**Returns:** DTO instance

**Example:**
```php
$dto = UserDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

---

#### fromJson()

**Signature:**
```php
public static function fromJson(string $json): static
```

**Description:** Create DTO instance from JSON string.

**Parameters:**
- `$json` (string): JSON string

**Returns:** DTO instance

**Throws:** `\JsonException` if JSON is invalid

**Example:**
```php
$json = '{"id": 1, "name": "John Doe"}';
$dto = UserDTO::fromJson($json);
```

---

#### validateAndCreate()

**Signature:**
```php
public static function validateAndCreate(array $data): static
```

**Description:** Validate data and create DTO instance.

**Parameters:**
- `$data` (array): Input data

**Returns:** DTO instance

**Throws:** `ValidationException` if validation fails

**Example:**
```php
try {
    $dto = UserDTO::validateAndCreate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
}
```

---

#### fromModel() (Laravel)

**Signature:**
```php
public static function fromModel(Model $model): static
```

**Description:** Create DTO from Eloquent model.

**Parameters:**
- `$model` (Model): Eloquent model instance

**Returns:** DTO instance

**Requires:** `EloquentTrait`

**Example:**
```php
use event4u\DataHelpers\SimpleDTO\Traits\EloquentTrait;

class UserDTO extends SimpleDTO
{
    use EloquentTrait;
}

$user = User::find(1);
$dto = UserDTO::fromModel($user);
```

---

#### fromEntity() (Symfony)

**Signature:**
```php
public static function fromEntity(object $entity): static
```

**Description:** Create DTO from Doctrine entity.

**Parameters:**
- `$entity` (object): Doctrine entity instance

**Returns:** DTO instance

**Requires:** `DoctrineTrait`

**Example:**
```php
use event4u\DataHelpers\SimpleDTO\Traits\DoctrineTrait;

class UserDTO extends SimpleDTO
{
    use DoctrineTrait;
}

$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);
```

---

### Instance Methods

#### toArray()

**Signature:**
```php
public function toArray(): array
```

**Description:** Convert DTO to array.

**Returns:** Array representation

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe']
```

---

#### toJson()

**Signature:**
```php
public function toJson(int $options = 0): string
```

**Description:** Convert DTO to JSON string.

**Parameters:**
- `$options` (int): JSON encoding options (default: 0)

**Returns:** JSON string

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$json = $dto->toJson(JSON_PRETTY_PRINT);
// {"id": 1, "name": "John Doe"}
```

---

#### toXml()

**Signature:**
```php
public function toXml(string $rootElement = 'root'): string
```

**Description:** Convert DTO to XML string.

**Parameters:**
- `$rootElement` (string): Root element name (default: 'root')

**Returns:** XML string

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$xml = $dto->toXml('user');
// <user><id>1</id><name>John Doe</name></user>
```

---

#### toYaml()

**Signature:**
```php
public function toYaml(): string
```

**Description:** Convert DTO to YAML string.

**Returns:** YAML string

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$yaml = $dto->toYaml();
// id: 1
// name: John Doe
```

---

#### toCsv()

**Signature:**
```php
public function toCsv(array $headers = []): string
```

**Description:** Convert DTO to CSV string.

**Parameters:**
- `$headers` (array): CSV headers (default: property names)

**Returns:** CSV string

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$csv = $dto->toCsv();
// id,name
// 1,"John Doe"
```

---

#### with()

**Signature:**
```php
public function with(string $key, mixed $value): static
```

**Description:** Add dynamic property to DTO.

**Parameters:**
- `$key` (string): Property name
- `$value` (mixed): Property value

**Returns:** New DTO instance with added property

**Example:**
```php
$dto = new UserDTO(id: 1, name: 'John Doe');
$dtoWithExtra = $dto->with('role', 'admin');

$array = $dtoWithExtra->toArray();
// ['id' => 1, 'name' => 'John Doe', 'role' => 'admin']
```

---

#### withContext()

**Signature:**
```php
public function withContext(array $context): static
```

**Description:** Add context for conditional properties.

**Parameters:**
- `$context` (array): Context data

**Returns:** New DTO instance with context

**Example:**
```php
$dto = new UserDTO(
    id: 1,
    name: 'John Doe',
    profile: ['bio' => 'Developer'],
);

$dtoWithContext = $dto->withContext(['include_profile' => true]);
$array = $dtoWithContext->toArray();
// ['id' => 1, 'name' => 'John Doe', 'profile' => ['bio' => 'Developer']]
```

---

#### toModel() (Laravel)

**Signature:**
```php
public function toModel(string $modelClass): Model
```

**Description:** Convert DTO to Eloquent model.

**Parameters:**
- `$modelClass` (string): Model class name

**Returns:** Model instance

**Requires:** `EloquentTrait`

**Example:**
```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');
$user = $dto->toModel(User::class);
$user->save();
```

---

#### toEntity() (Symfony)

**Signature:**
```php
public function toEntity(string $entityClass): object
```

**Description:** Convert DTO to Doctrine entity.

**Parameters:**
- `$entityClass` (string): Entity class name

**Returns:** Entity instance

**Requires:** `DoctrineTrait`

**Example:**
```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');
$user = $dto->toEntity(User::class);
$entityManager->persist($user);
$entityManager->flush();
```

---

## DataCollection Class

**Namespace:** `event4u\DataHelpers\SimpleDTO\DataCollection`

**Description:** Collection of DTOs with Laravel-like methods.

---

### Static Factory Methods

#### make()

**Signature:**
```php
public static function make(array $items, string $dtoClass): static
```

**Description:** Create collection from array of data.

**Parameters:**
- `$items` (array): Array of data
- `$dtoClass` (string): DTO class name

**Returns:** DataCollection instance

**Example:**
```php
$users = [
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
];

$collection = DataCollection::make($users, UserDTO::class);
```

---

### Collection Methods

#### count()

**Signature:**
```php
public function count(): int
```

**Description:** Get number of items in collection.

**Returns:** Item count

**Example:**
```php
$count = $collection->count(); // 2
```

---

#### filter()

**Signature:**
```php
public function filter(callable $callback): static
```

**Description:** Filter collection by callback.

**Parameters:**
- `$callback` (callable): Filter function

**Returns:** New filtered collection

**Example:**
```php
$activeUsers = $collection->filter(fn($user) => $user->isActive);
```

---

#### map()

**Signature:**
```php
public function map(callable $callback): static
```

**Description:** Transform each item in collection.

**Parameters:**
- `$callback` (callable): Transform function

**Returns:** New transformed collection

**Example:**
```php
$names = $collection->map(fn($user) => $user->name);
```

---

#### pluck()

**Signature:**
```php
public function pluck(string $key): array
```

**Description:** Extract values for a given key.

**Parameters:**
- `$key` (string): Property name

**Returns:** Array of values

**Example:**
```php
$names = $collection->pluck('name');
// ['John', 'Jane']
```

---

#### sortBy()

**Signature:**
```php
public function sortBy(string $key, bool $descending = false): static
```

**Description:** Sort collection by property.

**Parameters:**
- `$key` (string): Property name
- `$descending` (bool): Sort descending (default: false)

**Returns:** New sorted collection

**Example:**
```php
$sorted = $collection->sortBy('name');
$sortedDesc = $collection->sortBy('name', true);
```

---

#### groupBy()

**Signature:**
```php
public function groupBy(string $key): array
```

**Description:** Group collection by property.

**Parameters:**
- `$key` (string): Property name

**Returns:** Grouped array

**Example:**
```php
$grouped = $collection->groupBy('role');
// ['admin' => [...], 'user' => [...]]
```

---

#### first()

**Signature:**
```php
public function first(?callable $callback = null): mixed
```

**Description:** Get first item in collection.

**Parameters:**
- `$callback` (callable|null): Optional filter function

**Returns:** First item or null

**Example:**
```php
$first = $collection->first();
$firstAdmin = $collection->first(fn($user) => $user->role === 'admin');
```

---

#### last()

**Signature:**
```php
public function last(?callable $callback = null): mixed
```

**Description:** Get last item in collection.

**Parameters:**
- `$callback` (callable|null): Optional filter function

**Returns:** Last item or null

**Example:**
```php
$last = $collection->last();
```

---

#### toArray()

**Signature:**
```php
public function toArray(): array
```

**Description:** Convert collection to array.

**Returns:** Array of DTO arrays

**Example:**
```php
$array = $collection->toArray();
// [
//   ['id' => 1, 'name' => 'John'],
//   ['id' => 2, 'name' => 'Jane'],
// ]
```

---

#### toJson()

**Signature:**
```php
public function toJson(int $options = 0): string
```

**Description:** Convert collection to JSON.

**Parameters:**
- `$options` (int): JSON encoding options

**Returns:** JSON string

**Example:**
```php
$json = $collection->toJson(JSON_PRETTY_PRINT);
```

---

#### paginate()

**Signature:**
```php
public function paginate(int $perPage = 15, int $page = 1): array
```

**Description:** Paginate collection.

**Parameters:**
- `$perPage` (int): Items per page (default: 15)
- `$page` (int): Current page (default: 1)

**Returns:** Paginated array with meta

**Example:**
```php
$paginated = $collection->paginate(10, 1);
// [
//   'data' => [...],
//   'meta' => ['current_page' => 1, 'total' => 50, ...]
// ]
```

---

**See Also:**
- [Attributes API](attributes.md)
- [Casts API](casts.md)
- [User Guide](../simple-dto/README.md)

