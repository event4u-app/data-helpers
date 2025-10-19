# Plain PHP Usage

Learn how to use SimpleDTO in plain PHP projects without any framework.

---

## 🎯 Overview

SimpleDTO works perfectly in plain PHP projects with **zero dependencies**:

- ✅ **No Framework Required** - Works standalone
- ✅ **Zero Dependencies** - Core has no dependencies
- ✅ **PHP 8.2+** - Modern PHP features
- ✅ **Composer** - Easy installation
- ✅ **PSR-4 Autoloading** - Standard autoloading

---

## 🚀 Installation

```bash
composer require event4u/data-helpers
```

That's it! No configuration needed.

---

## 📝 Basic Usage

### Create a DTO

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Create from array
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Access properties
echo $dto->name;  // John Doe
echo $dto->email; // john@example.com
echo $dto->age;   // 30

// Convert to array
$array = $dto->toArray();
print_r($array);

// Convert to JSON
$json = $dto->toJson();
echo $json;
```

---

## 🌐 Working with HTTP

### Processing POST Data

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}

// From $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dto = CreateUserDTO::fromArray($_POST);
    
    // Process DTO
    $user = [
        'name' => $dto->name,
        'email' => $dto->email,
        'password' => password_hash($dto->password, PASSWORD_DEFAULT),
    ];
    
    // Save to database
    // ...
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'user' => $user]);
}
```

### Processing JSON Input

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// From JSON input
$json = file_get_contents('php://input');
$dto = CreateUserDTO::fromJson($json);

// Process DTO
$user = [
    'name' => $dto->name,
    'email' => $dto->email,
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'user' => $user]);
```

---

## 🗄️ Working with Databases

### PDO Integration

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Connect to database
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'password');

// Fetch user
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([1]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Create DTO from database row
$dto = UserDTO::fromArray($row);

// Use DTO
echo $dto->name;
echo $dto->email;

// Insert user
$dto = UserDTO::fromArray($_POST);
$stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
$stmt->execute([$dto->name, $dto->email]);
```

### MySQLi Integration

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Connect to database
$mysqli = new mysqli('localhost', 'user', 'password', 'mydb');

// Fetch user
$result = $mysqli->query('SELECT * FROM users WHERE id = 1');
$row = $result->fetch_assoc();

// Create DTO from database row
$dto = UserDTO::fromArray($row);

// Insert user
$dto = UserDTO::fromArray($_POST);
$stmt = $mysqli->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
$stmt->bind_param('ss', $dto->name, $dto->email);
$stmt->execute();
```

---

## 🎯 Real-World Examples

### Example 1: REST API

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Set JSON header
header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Connect to database
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'password');

// Route handling
if ($method === 'GET' && $path === '/api/users') {
    // List users
    $stmt = $pdo->query('SELECT * FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dtos = array_map(fn($user) => UserDTO::fromArray($user), $users);
    
    echo json_encode($dtos);
    
} elseif ($method === 'POST' && $path === '/api/users') {
    // Create user
    $json = file_get_contents('php://input');
    $dto = UserDTO::fromJson($json);
    
    $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
    $stmt->execute([$dto->name, $dto->email]);
    
    $id = $pdo->lastInsertId();
    $dto = UserDTO::fromArray(['id' => $id, 'name' => $dto->name, 'email' => $dto->email]);
    
    http_response_code(201);
    echo json_encode($dto);
    
} elseif ($method === 'GET' && preg_match('/^\/api\/users\/(\d+)$/', $path, $matches)) {
    // Get user
    $id = $matches[1];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(UserDTO::fromArray($user));
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
```

### Example 2: Form Processing

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class ContactFormDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
        
        #[Required]
        public readonly string $message,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = ContactFormDTO::validateAndCreate($_POST);
        
        // Send email
        $to = 'admin@example.com';
        $subject = 'Contact Form Submission';
        $body = "Name: {$dto->name}\nEmail: {$dto->email}\nMessage: {$dto->message}";
        mail($to, $subject, $body);
        
        $success = 'Thank you for your message!';
    } catch (ValidationException $e) {
        $errors = $e->errors();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Form</title>
</head>
<body>
    <?php if (isset($success)): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>
    
    <?php if (isset($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $field => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <li><?= $message ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <textarea name="message" placeholder="Message" required></textarea>
        <button type="submit">Send</button>
    </form>
</body>
</html>
```

### Example 3: CSV Import

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}

// Read CSV file
$file = fopen('products.csv', 'r');
$header = fgetcsv($file);

$products = [];
while (($row = fgetcsv($file)) !== false) {
    $data = array_combine($header, $row);
    $dto = ProductDTO::fromArray($data);
    $products[] = $dto;
}

fclose($file);

// Process products
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'password');
$stmt = $pdo->prepare('INSERT INTO products (name, price, stock) VALUES (?, ?, ?)');

foreach ($products as $product) {
    $stmt->execute([$product->name, $product->price, $product->stock]);
}

echo "Imported " . count($products) . " products\n";
```

---

## 🔄 Working with Sessions

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class UserSessionDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
    ) {}
}

session_start();

// Store DTO in session
$dto = UserSessionDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'admin',
]);

$_SESSION['user'] = $dto->toArray();

// Retrieve DTO from session
if (isset($_SESSION['user'])) {
    $dto = UserSessionDTO::fromArray($_SESSION['user']);
    echo "Welcome, {$dto->name}!";
}
```

---

## 🎨 Working with Files

### File Upload

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class FileUploadDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly int $size,
        public readonly string $tmpName,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $dto = FileUploadDTO::fromArray([
        'name' => $_FILES['file']['name'],
        'type' => $_FILES['file']['type'],
        'size' => $_FILES['file']['size'],
        'tmpName' => $_FILES['file']['tmp_name'],
    ]);
    
    // Validate file
    if ($dto->size > 1024 * 1024) {
        die('File too large');
    }
    
    // Move file
    $destination = 'uploads/' . $dto->name;
    move_uploaded_file($dto->tmpName, $destination);
    
    echo "File uploaded successfully!";
}
```

---

## 💡 Best Practices

### 1. Use Autoloading

```php
// ✅ Good - use Composer autoloading
require_once __DIR__ . '/vendor/autoload.php';

// ❌ Bad - manual includes
require_once 'SimpleDTO.php';
require_once 'UserDTO.php';
```

### 2. Validate User Input

```php
// ✅ Good - validate input
$dto = UserDTO::validateAndCreate($_POST);

// ❌ Bad - no validation
$dto = UserDTO::fromArray($_POST);
```

### 3. Use Type Hints

```php
// ✅ Good - type hinted
function processUser(UserDTO $dto): void

// ❌ Bad - no type hints
function processUser($dto)
```

### 4. Handle Errors

```php
// ✅ Good - error handling
try {
    $dto = UserDTO::validateAndCreate($_POST);
} catch (ValidationException $e) {
    echo json_encode(['errors' => $e->errors()]);
}

// ❌ Bad - no error handling
$dto = UserDTO::validateAndCreate($_POST);
```

---

## 📚 Next Steps

1. [Type Casting](06-type-casting.md) - Automatic type conversion
2. [Validation](07-validation.md) - Validate your data
3. [Collections](15-collections.md) - Working with collections
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [Symfony Integration](18-symfony-integration.md)  
**Next:** [Validation Attributes](20-validation-attributes.md)

