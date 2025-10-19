# Casts Reference

Complete reference of all type casts available in SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides 20+ built-in casts for automatic type conversion.

---

## 📋 Primitive Type Casts

### StringCast
Converts value to string.

```php
#[Cast(StringCast::class)]
public readonly string $name;
```

### IntegerCast
Converts value to integer.

```php
#[Cast(IntegerCast::class)]
public readonly int $age;
```

### FloatCast
Converts value to float.

```php
#[Cast(FloatCast::class)]
public readonly float $price;
```

### BooleanCast
Converts value to boolean.

```php
#[Cast(BooleanCast::class)]
public readonly bool $active;
```

---

## 📅 Date & Time Casts

### DateTimeCast
Converts to Carbon/DateTime instance.

```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(DateTimeCast::class, ['format' => 'Y-m-d H:i:s'])]
public readonly Carbon $customFormat;
```

### DateCast
Converts to date only (Y-m-d).

```php
#[Cast(DateCast::class)]
public readonly Carbon $birthDate;
```

### TimeCast
Converts to time only (H:i:s).

```php
#[Cast(TimeCast::class)]
public readonly Carbon $startTime;
```

---

## 🔢 Enum Casts

### EnumCast
Converts to PHP enum.

```php
#[Cast(EnumCast::class, ['enum' => UserRole::class])]
public readonly UserRole $role;
```

### BackedEnumCast
Converts to backed enum.

```php
#[Cast(BackedEnumCast::class, ['enum' => Status::class])]
public readonly Status $status;
```

---

## 📦 Collection Casts

### ArrayCast
Converts to array.

```php
#[Cast(ArrayCast::class)]
public readonly array $tags;
```

### CollectionCast
Converts to collection of DTOs.

```php
#[Cast(CollectionCast::class, ['itemType' => UserDTO::class])]
public readonly array $users;
```

---

## 🎨 Object Casts

### ObjectCast
Converts to nested DTO.

```php
#[Cast(ObjectCast::class, ['class' => AddressDTO::class])]
public readonly AddressDTO $address;
```

---

## 🔐 Security Casts

### EncryptedCast
Encrypts/decrypts value.

```php
#[Cast(EncryptedCast::class)]
public readonly string $ssn;
```

### HashCast
One-way hashing (for passwords).

```php
#[Cast(HashCast::class)]
public readonly string $password;
```

---

## 📚 Complete Reference

See [Type Casting](06-type-casting.md) for detailed documentation.

---

**Previous:** [Attributes Reference](33-attributes-reference.md)  
**Next:** [Traits Reference](35-traits-reference.md)

