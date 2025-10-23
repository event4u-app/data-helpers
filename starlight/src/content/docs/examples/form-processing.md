---
title: Form Processing Examples
description: Examples for processing HTML forms
---

Examples for processing HTML forms.

## Introduction

Common form processing patterns:

- ✅ **Contact Forms** - Simple contact forms
- ✅ **Registration** - User registration
- ✅ **Login** - User authentication
- ✅ **Profile Update** - Update user profile
- ✅ **File Upload** - Handle file uploads

## Contact Form

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = ContactFormDTO::validateAndCreate($_POST);
        
        // Send email
        mail(
            'admin@example.com',
            'Contact Form',
            "Name: {$dto->name}\nEmail: {$dto->email}\nMessage: {$dto->message}"
        );
        
        echo 'Message sent successfully!';
    } catch (ValidationException $e) {
        print_r($e->getErrors());
    }
}
```

## User Registration

```php
class RegisterDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(3), Max(50)]
        public readonly string $name,
        
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
        
        #[Required, Same('password')]
        public readonly string $passwordConfirmation,
        
        #[Required, Accepted]
        public readonly bool $terms,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = RegisterDTO::validateAndCreate($_POST);
        
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_DEFAULT),
        ]);
        
        // Send welcome email
        Mail::to($user)->send(new WelcomeEmail());
        
        // Log in user
        auth()->login($user);
        
        redirect('/dashboard');
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## Login Form

```php
class LoginDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required]
        public readonly string $password,
        
        public readonly bool $remember = false,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = LoginDTO::validateAndCreate($_POST);
        
        if (auth()->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ], $dto->remember)) {
            redirect('/dashboard');
        }
        
        back()->withErrors(['email' => 'Invalid credentials']);
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## Profile Update

```php
class UpdateProfileDTO extends SimpleDTO
{
    public function __construct(
        #[Min(3), Max(50)]
        public readonly ?string $name = null,
        
        #[Email]
        public readonly ?string $email = null,
        
        #[Min(10), Max(500)]
        public readonly ?string $bio = null,
        
        #[Url]
        public readonly ?string $website = null,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = UpdateProfileDTO::validateAndCreate($_POST);
        
        $user = auth()->user();
        
        // Only update provided fields
        $data = array_filter($dto->toArray(), fn($v) => $v !== null);
        
        $user->update($data);
        
        back()->with('success', 'Profile updated!');
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## File Upload

```php
class UploadAvatarDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Image, MaxFileSize(2048)]
        public readonly UploadedFile $avatar,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = UploadAvatarDTO::validateAndCreate($_FILES);
        
        $path = $dto->avatar->store('avatars', 'public');
        
        auth()->user()->update(['avatar' => $path]);
        
        back()->with('success', 'Avatar uploaded!');
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## Multi-Step Form

```php
// Step 1: Personal Info
class PersonalInfoDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $firstName,
        
        #[Required]
        public readonly string $lastName,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}

// Step 2: Address
class AddressDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $street,
        
        #[Required]
        public readonly string $city,
        
        #[Required]
        public readonly string $zipCode,
    ) {}
}

// Step 3: Complete Registration
class CompleteRegistrationDTO extends SimpleDTO
{
    public function __construct(
        public readonly PersonalInfoDTO $personalInfo,
        public readonly AddressDTO $address,
        
        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// Handle steps
session_start();

if ($step === 1) {
    $dto = PersonalInfoDTO::validateAndCreate($_POST);
    $_SESSION['personal_info'] = $dto->toArray();
    redirect('/register/step-2');
}

if ($step === 2) {
    $dto = AddressDTO::validateAndCreate($_POST);
    $_SESSION['address'] = $dto->toArray();
    redirect('/register/step-3');
}

if ($step === 3) {
    $dto = CompleteRegistrationDTO::fromArray([
        'personalInfo' => $_SESSION['personal_info'],
        'address' => $_SESSION['address'],
        'password' => $_POST['password'],
    ]);
    
    $dto->validate();
    
    // Create user
    $user = User::create([
        'first_name' => $dto->personalInfo->firstName,
        'last_name' => $dto->personalInfo->lastName,
        'email' => $dto->personalInfo->email,
        'street' => $dto->address->street,
        'city' => $dto->address->city,
        'zip_code' => $dto->address->zipCode,
        'password' => password_hash($dto->password, PASSWORD_DEFAULT),
    ]);
    
    unset($_SESSION['personal_info'], $_SESSION['address']);
    
    redirect('/dashboard');
}
```

## Search Form

```php
class SearchDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $query,
        
        public readonly ?string $category = null,
        
        #[In(['asc', 'desc'])]
        public readonly string $sort = 'asc',
        
        #[Min(1)]
        public readonly int $page = 1,
    ) {}
}

$dto = SearchDTO::fromArray($_GET);
$dto->validate();

$results = Product::query()
    ->where('name', 'like', "%{$dto->query}%")
    ->when($dto->category, fn($q) => $q->where('category', $dto->category))
    ->orderBy('name', $dto->sort)
    ->paginate(20, ['*'], 'page', $dto->page);
```

## Newsletter Subscription

```php
class SubscribeDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        public readonly array $interests = [],
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = SubscribeDTO::validateAndCreate($_POST);
        
        Newsletter::create([
            'email' => $dto->email,
            'interests' => $dto->interests,
            'subscribed_at' => now(),
        ]);
        
        Mail::to($dto->email)->send(new ConfirmSubscription());
        
        back()->with('success', 'Subscribed successfully!');
    } catch (ValidationException $e) {
        back()->withErrors($e->getErrors());
    }
}
```

## See Also

- [Validation](/simple-dto/validation/) - Validation rules
- [File Upload](/examples/file-upload/) - File upload examples
- [Database Operations](/examples/database-operations/) - Database examples

