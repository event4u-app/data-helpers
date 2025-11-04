# Phase 2: SimpleDto-Klassen gesichert (Backup)

**Erstellt**: 2025-10-31
**Branch**: `refactor/litedto-becomes-simpledto`

---

## ✅ Backup erstellt

### Verzeichnis umbenannt

```bash
src/SimpleDto/ → src/SimpleDto.bak/
```

### Backup-Inhalt

**Verzeichnisse** in `src/SimpleDto.bak/`:
- `Attributes/` - Alle Attribute (72 Dateien)
- `Casts/` - Alle Cast-Klassen (17 Dateien)
- `CodeGen/` - Code-Generation
- `Concerns/` - Traits und Concerns
- `Config/` - Konfiguration
- `Contracts/` - Interfaces
- `Enums/` - Enumerations
- `Normalizers/` - Normalizer-Klassen
- `Pipeline/` - Pipeline-Klassen
- `Serializers/` - Serializer-Klassen

**Hauptdateien** in `src/SimpleDto.bak/`:
- `DataCollection.php` - Collection-Klasse
- `DtoFactory.php` - Factory-Klasse
- `DtoInterface.php` - Interface
- `SimpleDtoBenchmarkTrait.php` - Benchmark-Trait
- `SimpleDtoCastsTrait.php` - Casts-Trait
- `SimpleDtoComputedTrait.php` - Computed-Trait
- `SimpleDtoConditionalTrait.php` - Conditional-Trait
- ... und viele weitere Traits

**Gesamt**: ~150 Dateien

---

## ✅ Tests bleiben unverändert

### SimpleDto-Tests (bleiben erhalten)

**Unit Tests**: `tests/Unit/SimpleDto/`
- ~60 Test-Dateien
- Alle Tests bleiben unverändert
- Werden später mit neuem SimpleDto getestet

**Integration Tests**: `tests/Integration/SimpleDto/`
- Alle Tests bleiben unverändert
- Werden später mit neuem SimpleDto getestet

---

## 📁 Aktuelle Struktur

### src/

```
src/
├── LiteDto/                    ← Wird zu SimpleDto (Phase 3)
├── SimpleDto.bak/              ← Backup (wird am Ende gelöscht)
├── SimpleDto.php               ← Bleibt (Facade/Alias)
├── DataAccessor/
├── DataFilter/
├── DataMapper/
└── ...
```

### tests/

```
tests/
├── Unit/
│   ├── SimpleDto/              ← Tests bleiben (müssen mit neuem SimpleDto funktionieren)
│   ├── LiteDto/                ← Tests werden migriert (Phase 7)
│   └── ...
└── Integration/
    ├── SimpleDto/              ← Tests bleiben
    ├── LiteDto/                ← Tests werden migriert (Phase 7)
    └── ...
```

---

## 🎯 Verwendung des Backups

### Code-Vergleich

```bash
# Vergleiche alte und neue Implementierung
diff src/SimpleDto.bak/SimpleDto.php src/SimpleDto/SimpleDto.php

# Suche nach Features in alter Implementierung
grep -r "someFeature" src/SimpleDto.bak/

# Zeige alte Implementierung an
cat src/SimpleDto.bak/SimpleDtoTrait.php
```

### Referenz für Migration

Das Backup dient als:
1. **Vorlage**: Wie waren Features implementiert?
2. **Vergleich**: Was hat sich geändert?
3. **Sicherheit**: Falls etwas schiefgeht, können wir zurück
4. **Dokumentation**: Wie funktionierte die alte Version?

---

## 🔄 Rollback (falls nötig)

### Option 1: Backup wiederherstellen

```bash
# Neues SimpleDto löschen
rm -rf src/SimpleDto/

# Backup zurück umbenennen
mv src/SimpleDto.bak src/SimpleDto

# Commit rückgängig machen
git reset --hard HEAD~1
```

### Option 2: Zum Tag zurück

```bash
git checkout backup-before-litedto-migration
```

---

## ✅ Phase 2 - Abgeschlossen

**Ergebnis**:
- ✅ `src/SimpleDto/` → `src/SimpleDto.bak/` umbenannt
- ✅ Backup enthält ~150 Dateien
- ✅ Tests bleiben unverändert in `tests/Unit/SimpleDto/` und `tests/Integration/SimpleDto/`
- ✅ Backup dient als Referenz für Code-Vergleiche

**Nächster Schritt**: Phase 3 - LiteDto zu SimpleDto kopieren

---

**Letzte Aktualisierung**: 2025-10-31

