# Phase 3: LiteDto zu SimpleDto kopiert

**Erstellt**: 2025-10-31
**Branch**: `refactor/litedto-becomes-simpledto`

---

## ✅ LiteDto kopiert

### Verzeichnis kopiert

```bash
src/LiteDto/ → src/SimpleDto/ (kopiert)
```

**Wichtig**: LiteDto bleibt vorerst erhalten! Wird erst in Phase 8 gelöscht.

---

## 📁 Aktuelle Struktur

### src/

```
src/
├── LiteDto/              ← Original (bleibt vorerst)
├── SimpleDto/            ← Kopie von LiteDto (NEU)
├── SimpleDto.bak/        ← Backup der alten SimpleDto-Implementierung
├── SimpleDto.php         ← Facade (bleibt)
└── ...
```

### src/SimpleDto/ (Kopie von LiteDto)

**Verzeichnisse**:
- `Attributes/` - 30 Attribute
- `Casters/` - 4 Caster-Klassen
- `Contracts/` - 6 Interfaces
- `Support/` - 3 Support-Klassen

**Hauptdateien**:
- `LiteDto.php` - Hauptklasse (wird in Phase 4 umbenannt)
- `ImmutableLiteDto.php` - Immutable-Variante (wird in Phase 4 umbenannt)

**Gesamt**: ~50 Dateien

---

## 🎯 Nächste Schritte

### Phase 4: Namespaces & Imports aktualisieren

In Phase 4 werden wir:
1. Alle Klassen umbenennen: `LiteDto` → `SimpleDto`
2. Alle Namespaces ändern: `event4u\DataHelpers\LiteDto` → `event4u\DataHelpers\SimpleDto`
3. Alle Imports aktualisieren
4. Alle Referenzen aktualisieren

### Was bleibt vorerst unverändert

- `src/LiteDto/` - Bleibt als Referenz (wird in Phase 8 gelöscht)
- `src/SimpleDto.bak/` - Bleibt als Backup (wird in Phase 9 gelöscht)
- Tests - Bleiben unverändert (werden in Phase 7 migriert)

---

## 📊 Dateien-Übersicht

### src/SimpleDto/ (NEU)

```
src/SimpleDto/
├── Attributes/
│   ├── AutoCast.php
│   ├── Computed.php
│   ├── ConditionalValidation.php
│   ├── Hidden.php
│   ├── Lazy.php
│   ├── MapFrom.php
│   ├── NoCasts.php
│   ├── Optional.php
│   ├── RuleGroup.php
│   ├── UltraFast.php
│   ├── WhenCallback.php
│   ├── WhenContext.php
│   ├── WhenContextEquals.php
│   ├── WhenContextIn.php
│   ├── WhenContextNotNull.php
│   ├── WhenEquals.php
│   ├── WhenFalse.php
│   ├── WhenIn.php
│   ├── WhenNotNull.php
│   ├── WhenNull.php
│   ├── WhenTrue.php
│   ├── WhenValue.php
│   ├── WithMessage.php
│   └── ... (weitere Validation-Attributes)
├── Casters/
│   ├── DateTimeCaster.php
│   ├── DtoCaster.php
│   ├── EnumCaster.php
│   └── JsonCaster.php
├── Contracts/
│   ├── Caster.php
│   ├── ConditionalProperty.php
│   ├── ConditionalValidation.php
│   ├── DtoContract.php
│   ├── HasContext.php
│   └── ValidationRule.php
├── Support/
│   ├── LiteEngine.php (wird umbenannt)
│   ├── Optional.php
│   └── ValidationResult.php
├── LiteDto.php (wird umbenannt zu SimpleDto.php)
└── ImmutableLiteDto.php (wird umbenannt zu ImmutableSimpleDto.php)
```

---

## ✅ Phase 3 - Abgeschlossen

**Ergebnis**:
- ✅ `src/LiteDto/` nach `src/SimpleDto/` kopiert
- ✅ ~50 Dateien kopiert
- ✅ LiteDto bleibt vorerst erhalten
- ✅ Struktur validiert

**Nächster Schritt**: Phase 4 - Namespaces & Imports aktualisieren

---

**Letzte Aktualisierung**: 2025-10-31

