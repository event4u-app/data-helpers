---
title: Database Operations Examples
description: Examples for database CRUD operations
---

Examples for database CRUD operations.

## Introduction

Common database patterns:

- ✅ **Create** - Insert new records
- ✅ **Read** - Fetch records
- ✅ **Update** - Update records
- ✅ **Delete** - Delete records
- ✅ **Relationships** - Handle relationships

## Create (Insert)

```php
class CreateUserDto extends SimpleDto
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

$dto = CreateUserDto::validateAndCreate($_POST);

$user = User::create([
    'name' => $dto->name,
    'email' => $dto->email,
    'password' => password_hash($dto->password, PASSWORD_DEFAULT),
]);

// Or use toModel()
$user = $dto->toModel(User::class);
$user->save();
```

## Read (Fetch)

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly Carbon $createdAt,
    ) {}
}

// Single record
$user = User::find(1);
$dto = UserDto::fromModel($user);

// Multiple records
$users = User::all();
$dtos = $users->map(fn($user) => UserDto::fromModel($user));

// With DataCollection
$dtos = DataCollection::make($users, UserDto::class);
```

## Update

```php
class UpdateUserDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
    ) {}
}

$dto = UpdateUserDto::validateAndCreate($_POST);

$user = User::find($id);

// Only update provided fields
$data = array_filter($dto->toArray(), fn($v) => $v !== null);
$user->update($data);

// Or use toModel()
$dto->toModel($user);
$user->save();
```

## Delete

```php
$user = User::find($id);

// Check permissions
if (auth()->user()->can('delete', $user)) {
    $user->delete();
}
```

## Relationships

### One-to-Many

```php
class PostDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        public readonly UserDto $author,
        public readonly array $comments,
    ) {}
}

$post = Post::with(['author', 'comments'])->find(1);
$dto = PostDto::fromModel($post);
```

### Many-to-Many

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $roles,
    ) {}
}

$user = User::with('roles')->find(1);
$dto = UserDto::fromModel($user);
```

## Pagination

```php
class PaginatedUsersDto extends SimpleDto
{
    public function __construct(
        public readonly array $data,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $total,
    ) {}
}

$users = User::paginate(20);

$dto = PaginatedUsersDto::fromArray([
    'data' => $users->map(fn($u) => UserDto::fromModel($u))->toArray(),
    'currentPage' => $users->currentPage(),
    'lastPage' => $users->lastPage(),
    'total' => $users->total(),
]);
```

## Filtering

```php
class UserFilterDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $role = null,
        public readonly ?Carbon $createdAfter = null,
    ) {}
}

$filter = UserFilterDto::fromArray($_GET);

$users = User::query()
    ->when($filter->name, fn($q) => $q->where('name', 'like', "%{$filter->name}%"))
    ->when($filter->email, fn($q) => $q->where('email', $filter->email))
    ->when($filter->role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $filter->role)))
    ->when($filter->createdAfter, fn($q) => $q->where('created_at', '>=', $filter->createdAfter))
    ->get();
```

## Bulk Operations

### Bulk Insert

<!-- skip-test: Requires Laravel Eloquent and CreateUserDto -->
```php
$dtos = [
    CreateUserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']),
    CreateUserDto::fromArray(['name' => 'Jane', 'email' => 'jane@example.com']),
];

$data = array_map(fn($dto) => $dto->toArray(), $dtos);

User::insert($data);
```

### Bulk Update

<!-- skip-test: Requires Laravel Eloquent -->
```php
User::whereIn('id', [1, 2, 3])->update(['status' => 'active']);
```

## Transactions

<!-- skip-test: Requires Laravel Eloquent -->
```php
DB::transaction(function() use ($dto) {
    $user = User::create($dto->toArray());

    $profile = Profile::create([
        'user_id' => $user->id,
        'bio' => $dto->bio,
    ]);

    $user->roles()->attach($dto->roleIds);
});
```

## Soft Deletes

```php
// Soft delete
$user = User::find($id);
$user->delete();

// Restore
$user = User::withTrashed()->find($id);
$user->restore();

// Force delete
$user->forceDelete();
```

## Eager Loading

```php
class PostDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly UserDto $author,
        public readonly array $comments,
        public readonly array $tags,
    ) {}
}

$posts = Post::with(['author', 'comments', 'tags'])->get();
$dtos = $posts->map(fn($post) => PostDto::fromModel($post));
```

## See Also

- [Creating Dtos](/simple-dto/creating-dtos/) - Dto creation methods
- [Nested Dtos](/simple-dto/nested-dtos/) - Nested Dtos
- [Collections](/simple-dto/collections/) - DataCollection

