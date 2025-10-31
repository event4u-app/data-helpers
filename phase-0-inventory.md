# Phase 0: Inventar - LiteDto → SimpleDto Migration

**Erstellt**: 2025-10-31
**Status**: Abgeschlossen

---

## 📁 Datei-Inventar

### 1. SimpleDto-Dateien (src/SimpleDto/)

**Hauptdateien** (werden durch LiteDto ersetzt):
- `DataCollection.php` - Collection-Implementierung
- `DtoFactory.php` - Factory für DTOs
- `DtoInterface.php` - Interface
- `SimpleDtoBenchmarkTrait.php` - Benchmark-Trait
- `SimpleDtoCastsTrait.php` - Casts-Trait
- `SimpleDtoComputedTrait.php` - Computed Properties
- `SimpleDtoConditionalTrait.php` - Conditional Properties
- `SimpleDtoDiffTrait.php` - Diff-Funktionalität
- `SimpleDtoDoctrineTrait.php` - Doctrine-Integration
- `SimpleDtoDoctrineType.php` - Doctrine Type
- `SimpleDtoEloquentCast.php` - Eloquent Cast
- `SimpleDtoEloquentTrait.php` - Eloquent-Integration
- `SimpleDtoImporterTrait.php` - Import-Funktionalität
- `SimpleDtoLazyCastTrait.php` - Lazy Casts
- `SimpleDtoLazyTrait.php` - Lazy Properties
- `SimpleDtoMapperTrait.php` - Mapper-Trait
- `SimpleDtoMappingTrait.php` - Mapping-Funktionalität
- `SimpleDtoNormalizerTrait.php` - Normalizer
- `SimpleDtoOptionalTrait.php` - Optional Properties
- `SimpleDtoPerformanceTrait.php` - Performance-Optimierungen
- `SimpleDtoPipelineTrait.php` - Pipeline
- `SimpleDtoRequestValidationTrait.php` - Request Validation
- `SimpleDtoSerializerTrait.php` - Serializer
- `SimpleDtoSortingTrait.php` - Sorting
- `SimpleDtoTrait.php` - Haupt-Trait
- `SimpleDtoTransformerTrait.php` - Transformer
- `SimpleDtoValidationTrait.php` - Validation
- `SimpleDtoVisibilityTrait.php` - Visibility
- `SimpleDtoWithTrait.php` - With-Funktionalität
- `SimpleDtoWrappingTrait.php` - Wrapping
- `TypeScriptGenerator.php` - TypeScript-Generator
- `ValidationErrorCollection.php` - Validation Errors

**Unterverzeichnisse**:
- `Attributes/` - 100+ Attribute-Klassen
- `Casts/` - 15 Cast-Klassen
- `CodeGen/` - Code-Generation
- `Concerns/` - Shared Concerns
- `Config/` - Konfiguration
- `Contracts/` - Interfaces
- `Enums/` - Enumerations
- `Normalizers/` - Normalizer
- `Pipeline/` - Pipeline-Stages
- `Serializers/` - Serializer
- `Support/` - Support-Klassen (UltraFastEngine, FastPath, etc.)
- `Transformers/` - Transformer

**Geschätzte Anzahl Dateien**: ~150+ Dateien

---

### 2. LiteDto-Dateien (src/LiteDto/)

**Hauptdateien**:
- `LiteDto.php` - Haupt-Klasse
- `ImmutableLiteDto.php` - Immutable Variante

**Unterverzeichnisse**:
- `Attributes/` - ~40 Attribute-Klassen
  - `Conditional/` - Conditional Attributes
  - `Laravel/` - Laravel-spezifische Attributes
  - `Symfony/` - Symfony-spezifische Attributes
  - `Validation/` - Validation Attributes
- `Casters/` - 2 Caster-Klassen
- `Contracts/` - 4 Interface-Klassen
- `Support/` - 1 Support-Klasse (LiteEngine.php)

**Geschätzte Anzahl Dateien**: ~50 Dateien

---

### 3. SimpleDto-Tests (tests/Unit/SimpleDto/)

**Test-Dateien**: 60+ Test-Dateien

**Wichtige Tests**:
- `SimpleDtoTest.php` - Haupt-Tests
- `UltraFastTest.php` - UltraFast Mode Tests
- `SimpleDtoValidationTest.php` - Validation Tests
- `SimpleDtoCastsTest.php` - Casts Tests
- `SimpleDtoMappingTest.php` - Mapping Tests
- `ComputedPropertiesTest.php` - Computed Properties Tests
- `LazyPropertiesTest.php` - Lazy Properties Tests
- `PerformanceTest.php` - Performance Tests
- `FastPathTest.php` - FastPath Tests
- `DataCollectionTest.php` - Collection Tests
- `EloquentIntegrationTest.php` - Eloquent Tests
- `DoctrineIntegrationTest.php` - Doctrine Tests
- ... und viele mehr

**Unterverzeichnisse**:
- `Attributes/` - Attribute-Tests
- `Enums/` - Enum-Tests
- `FastPath/` - FastPath-Tests
- `Fixtures/` - Test-Fixtures

**Status**: Diese Tests bleiben erhalten und müssen mit neuem SimpleDto funktionieren!

---

### 4. SimpleDto-Integration-Tests (tests/Integration/SimpleDto/)

**Status**: Verzeichnis existiert nicht
**Hinweis**: Keine Integration-Tests für SimpleDto vorhanden

---

### 5. LiteDto-Tests (tests/Unit/LiteDto/)

**Test-Dateien**: 19 Test-Dateien

**Wichtige Tests**:
- `LiteDtoTest.php` - Haupt-Tests
- `UltraFastTest.php` - UltraFast Mode Tests
- `HooksTest.php` - Lifecycle Hooks Tests
- `ValidationAttributesTest.php` - Validation Tests
- `MappingAttributesTest.php` - Mapping Tests
- `ComputedLazyTest.php` - Computed & Lazy Tests
- `VisibilityAttributesTest.php` - Visibility Tests
- `EnumTest.php` - Enum Tests
- `CastWithTest.php` - Custom Casts Tests
- `ImmutableLiteDtoTest.php` - Immutable Tests
- ... und mehr

**Status**: Diese Tests werden zu SimpleDto migriert

---

### 6. LiteDto-Integration-Tests (tests/Integration/LiteDto/)

**Test-Dateien**: 12 Test-Dateien

**Wichtige Tests**:
- `AutoCastTest.php` - AutoCast Tests
- `ConditionalPropertiesTest.php` - Conditional Properties Tests
- `DateTimeCastingTest.php` - DateTime Casting Tests
- `LaravelConditionalAttributesTest.php` - Laravel Tests
- `SymfonyConditionalAttributesTest.php` - Symfony Tests
- `OptionalPropertiesTest.php` - Optional Properties Tests
- `PerformanceAttributesTest.php` - Performance Tests
- `RuleGroupTest.php` - RuleGroup Tests
- `WithMessageTest.php` - WithMessage Tests
- `ValidationCallbackWithPdoTest.php` - Validation Callback Tests
- `ValidationFileCallbackTest.php` - File Validation Tests
- ... und mehr

**Status**: Diese Tests werden zu SimpleDto migriert

---

## 📊 Performance-Baseline (Aktuelle Zahlen)

### Aus dto-comparison.md:

| DTO Type | Performance | Use Case |
|----------|-------------|----------|
| **SimpleDto** | ~24.8μs | Full-featured mit Validation |
| **LiteDto** | ~4.8μs | Performance-optimiert |
| **LiteDto #[UltraFast]** | ~3.0μs | Maximum Performance |

### Performance-Ziele nach Migration:

| DTO Type | Aktuell | Ziel |
|----------|---------|------|
| SimpleDto (alt) | ~24.8μs | - |
| LiteDto | ~4.8μs | - |
| LiteDto #[UltraFast] | ~3.0μs | - |
| **SimpleDto (neu, Standard)** | - | **~3.0μs** |
| **SimpleDto (neu, #[UltraFast])** | - | **~0.5μs** |

**Verbesserung**: 
- Standard: ~8.3x schneller (24.8μs → 3.0μs)
- UltraFast: ~49.6x schneller (24.8μs → 0.5μs)

---

## 📈 Statistiken

### Dateien

| Kategorie | Anzahl | Status |
|-----------|--------|--------|
| SimpleDto-Klassen | ~150 | → SimpleDto.bak |
| LiteDto-Klassen | ~50 | → SimpleDto |
| SimpleDto-Tests | ~60 | Behalten |
| LiteDto-Tests (Unit) | 19 | Migrieren |
| LiteDto-Tests (Integration) | 12 | Migrieren |

### Tests

| Kategorie | Anzahl | Status |
|-----------|--------|--------|
| SimpleDto Unit Tests | ~60 | Müssen bestehen |
| LiteDto Unit Tests | 19 | Zu SimpleDto migrieren |
| LiteDto Integration Tests | 12 | Zu SimpleDto migrieren |
| **Gesamt nach Migration** | **~91** | Alle müssen bestehen |

---

## 🎯 Migrations-Umfang

### Was wird ersetzt:

1. **Haupt-Klassen**:
   - SimpleDto → LiteDto (umbenannt)
   - SimpleDtoTrait → LiteDtoTrait (umbenannt)
   - SimpleDtoEngine → LiteEngine (umbenannt)

2. **Features**:
   - Alle LiteDto-Features werden zu SimpleDto-Features
   - UltraFast Mode wird Standard
   - Lifecycle Hooks verfügbar
   - Alle Validation Attributes
   - Alle Mapping Attributes
   - Alle Conditional Attributes

3. **Performance**:
   - Standard: ~8.3x schneller
   - UltraFast: ~49.6x schneller (Ziel)

### Was bleibt:

1. **Tests**:
   - Alle SimpleDto-Tests bleiben
   - Werden mit neuem SimpleDto validiert

2. **Shared Components**:
   - Attributes (soweit kompatibel)
   - Casts (soweit kompatibel)
   - Contracts/Interfaces

---

## ✅ Phase 0 - Abgeschlossen

**Ergebnis**:
- ✅ Vollständiges Inventar erstellt
- ✅ Performance-Baseline dokumentiert
- ✅ Migrations-Umfang klar definiert
- ✅ Test-Strategie definiert

**Nächster Schritt**: Phase 1 - Backup & Inventar (Git-Branch erstellen)

---

**Letzte Aktualisierung**: 2025-10-31

