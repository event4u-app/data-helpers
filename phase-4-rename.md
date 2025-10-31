# Phase 4: Namespaces & Imports aktualisiert

**Erstellt**: 2025-10-31
**Branch**: `refactor/litedto-becomes-simpledto`

---

## ✅ Dateien umbenannt

### Hauptdateien

```bash
src/SimpleDto/LiteDto.php → src/SimpleDto/SimpleDto.php
src/SimpleDto/ImmutableLiteDto.php → src/SimpleDto/ImmutableSimpleDto.php
src/SimpleDto/Support/LiteEngine.php → src/SimpleDto/Support/SimpleEngine.php
```

---

## ✅ Namespaces aktualisiert

### Alle Dateien in src/SimpleDto/

**Vorher**:
```php
namespace event4u\DataHelpers\LiteDto;
namespace event4u\DataHelpers\LiteDto\Attributes;
namespace event4u\DataHelpers\LiteDto\Support;
// etc.
```

**Nachher**:
```php
namespace event4u\DataHelpers\SimpleDto;
namespace event4u\DataHelpers\SimpleDto\Attributes;
namespace event4u\DataHelpers\SimpleDto\Support;
// etc.
```

---

## ✅ Imports aktualisiert

### Alle use-Statements

**Vorher**:
```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
// etc.
```

**Nachher**:
```php
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
// etc.
```

---

## ✅ Klassennamen aktualisiert

### Hauptklassen

- `class LiteDto` → `class SimpleDto`
- `class ImmutableLiteDto` → `class ImmutableSimpleDto`
- `class LiteEngine` → `class SimpleEngine`

### Alle Referenzen

- `extends LiteDto` → `extends SimpleDto`
- `instanceof LiteDto` → `instanceof SimpleDto`
- `LiteEngine::` → `SimpleEngine::`
- `LiteDto\` → `SimpleDto\`

---

## ✅ Kommentare & Dokumentation aktualisiert

### Alle Erwähnungen in Kommentaren

- "LiteDto" → "SimpleDto"
- "LiteEngine" → "SimpleEngine"
- "ImmutableLiteDto" → "ImmutableSimpleDto"

### Beispiele in PHPDoc

**Vorher**:
```php
/**
 * Example:
 *   class UserDto extends LiteDto {
 *       // ...
 *   }
 */
```

**Nachher**:
```php
/**
 * Example:
 *   class UserDto extends SimpleDto {
 *       // ...
 *   }
 */
```

---

## 📊 Geänderte Dateien

### Gesamt: ~88 Dateien

**Hauptdateien**:
- `src/SimpleDto/SimpleDto.php` (379 Zeilen)
- `src/SimpleDto/ImmutableSimpleDto.php` (70 Zeilen)
- `src/SimpleDto/Support/SimpleEngine.php` (2676 Zeilen)

**Attributes** (~40 Dateien):
- `src/SimpleDto/Attributes/*.php`
- `src/SimpleDto/Attributes/Conditional/*.php`
- `src/SimpleDto/Attributes/Laravel/*.php`
- `src/SimpleDto/Attributes/Symfony/*.php`
- `src/SimpleDto/Attributes/Validation/*.php`

**Contracts** (4 Dateien):
- `src/SimpleDto/Contracts/CasterInterface.php`
- `src/SimpleDto/Contracts/ConditionalProperty.php`
- `src/SimpleDto/Contracts/ConditionalValidationAttribute.php`
- `src/SimpleDto/Contracts/ValidationAttribute.php`

**Casters** (2 Dateien):
- `src/SimpleDto/Casters/DateTimeCaster.php`
- `src/SimpleDto/Casters/DateTimeImmutableCaster.php`

**Support** (1 Datei):
- `src/SimpleDto/Support/Optional.php`
- `src/SimpleDto/Support/ValidationResult.php`

---

## 🔍 Verwendete Befehle

```bash
# Dateien umbenennen
mv src/SimpleDto/LiteDto.php src/SimpleDto/SimpleDto.php
mv src/SimpleDto/ImmutableLiteDto.php src/SimpleDto/ImmutableSimpleDto.php
mv src/SimpleDto/Support/LiteEngine.php src/SimpleDto/Support/SimpleEngine.php

# Namespaces ändern
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/namespace event4u\\DataHelpers\\LiteDto/namespace event4u\\DataHelpers\\SimpleDto/g' {} \;

# Imports ändern
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/use event4u\\DataHelpers\\LiteDto/use event4u\\DataHelpers\\SimpleDto/g' {} \;

# Klassennamen ändern
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/class LiteEngine/class SimpleEngine/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/LiteEngine::/SimpleEngine::/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/extends LiteDto/extends SimpleDto/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/instanceof LiteDto/instanceof SimpleDto/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/LiteDto\\\\/SimpleDto\\\\/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/\\SimpleDto\\LiteDto/\\SimpleDto\\SimpleDto/g' {} \;

# Alle Erwähnungen in Kommentaren & Strings
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/LiteDto/SimpleDto/g' {} \;
find src/SimpleDto -type f -name "*.php" -exec sed -i '' 's/LiteEngine/SimpleEngine/g' {} \;
```

---

## 🎯 Nächster Schritt: Phase 5

**Phase 5: UltraFast als Standard implementieren**

Was passiert in Phase 5:
1. UltraFast-Verhalten wird zum Standard (immer aktiv)
2. #[UltraFast] Attribut wird optional/deprecated
3. Tests aktualisieren
4. Performance validieren

---

## ✅ Phase 4 - Abgeschlossen

**Ergebnis**:
- ✅ 3 Hauptdateien umbenannt
- ✅ ~88 Dateien aktualisiert
- ✅ Alle Namespaces geändert: `LiteDto` → `SimpleDto`
- ✅ Alle Imports aktualisiert
- ✅ Alle Klassennamen geändert
- ✅ Alle Kommentare & Dokumentation aktualisiert

**Vorschlag für Commit-Message**:
```
Phase 4: Rename LiteDto to SimpleDto (namespaces & classes)

- Renamed main files: LiteDto.php → SimpleDto.php, ImmutableLiteDto.php → ImmutableSimpleDto.php, LiteEngine.php → SimpleEngine.php
- Updated all namespaces: event4u\DataHelpers\LiteDto → event4u\DataHelpers\SimpleDto
- Updated all imports and class references
- Updated all comments and documentation
- ~88 files changed
```

---

**Letzte Aktualisierung**: 2025-10-31

