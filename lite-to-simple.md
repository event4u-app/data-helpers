# LiteDto → SimpleDto Migration Plan

## 🎯 Ziel

**LiteDto wird das neue SimpleDto!**

Das aktuelle LiteDto mit #[UltraFast] Mode wird zum neuen Standard-SimpleDto. Danach wird ein noch schnelleres Super-UltraFast Mode implementiert mit dem Ziel von ~0.5μs oder weniger.

## 📋 Warum?

- **LiteDto ist ausgereift**: Alle Features von SimpleDto + Hooks + bessere Performance
- **UltraFast ist stabil**: Alle Tests bestehen, Feature-Parität erreicht
- **Vereinfachung**: Eine DTO-Klasse statt zwei
- **Performance**: Noch schnellere Performance durch neues Super-UltraFast Mode

## 🎯 Hauptziele

1. ✅ SimpleDto-Klassen löschen (Tests behalten!)
2. ✅ LiteDto zu SimpleDto umbenennen/kopieren
3. ✅ UltraFast Mode wird Standard (immer aktiv)
4. ✅ Neues Super-UltraFast Mode implementieren (~0.5μs Ziel)
5. ✅ Alle Tests migrieren und validieren
6. ✅ Dokumentation vollständig aktualisieren

## 📊 Aktueller Status

**Aktuelle Phase**: Phase 1 - Backup & Inventar (IN ARBEIT)

### Phasen-Übersicht

- [x] **Phase 0**: Vorbereitung & Analyse
- [/] **Phase 1**: Backup & Inventar
- [ ] **Phase 2**: SimpleDto-Klassen sichern (→ SimpleDto.bak)
- [ ] **Phase 3**: LiteDto zu SimpleDto kopieren
- [ ] **Phase 4**: Namespaces & Imports aktualisieren
- [ ] **Phase 5**: UltraFast als Standard implementieren
- [ ] **Phase 6**: Neues Super-UltraFast Mode (~0.5μs)
- [ ] **Phase 7**: Tests migrieren & validieren
- [ ] **Phase 8**: Dokumentation aktualisieren
- [ ] **Phase 9**: Finale Validierung & Cleanup (SimpleDto.bak löschen)

---

## 📝 Phase 0: Vorbereitung & Analyse

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [x] Abgeschlossen

### Ziel
Verstehen, was wir haben und was wir brauchen.

### Schritte

- [x] Liste aller SimpleDto-Dateien erstellen (`src/SimpleDto/`)
- [x] Liste aller SimpleDto-Tests erstellen (`tests/Unit/SimpleDto/`, `tests/Integration/SimpleDto/`)
- [x] Liste aller LiteDto-Dateien erstellen (`src/LiteDto/`)
- [x] Liste aller LiteDto-Tests erstellen (`tests/Unit/LiteDto/`, `tests/Integration/LiteDto/`)
- [x] Performance-Baseline messen (aktuelles SimpleDto vs LiteDto)
- [x] Dokumentations-Inventar erstellen

### Prompt für KI-Agent

```
Erstelle ein vollständiges Inventar für die LiteDto → SimpleDto Migration:

1. Liste alle Dateien in src/SimpleDto/ auf
2. Liste alle Dateien in src/LiteDto/ auf
3. Liste alle Test-Dateien in tests/Unit/SimpleDto/ und tests/Integration/SimpleDto/ auf
4. Liste alle Test-Dateien in tests/Unit/LiteDto/ und tests/Integration/LiteDto/ auf
5. Führe den Benchmark aus: docker exec data-helpers-php84 bash -c "php scripts/comprehensive-benchmark.php"
6. Erstelle eine Zusammenfassung der aktuellen Performance-Zahlen

Speichere die Ergebnisse in phase-0-inventory.md
```

### Erwartetes Ergebnis
- `phase-0-inventory.md` mit vollständigem Inventar
- Performance-Baseline dokumentiert
- Klares Bild von Umfang der Migration

### Validierung
- [x] Alle Dateien erfasst
- [x] Performance-Zahlen dokumentiert
- [x] Keine Dateien übersehen

### ✅ Ergebnis

**Inventar erstellt**: `phase-0-inventory.md`

**Wichtige Erkenntnisse**:
- SimpleDto: ~150 Dateien (→ SimpleDto.bak)
- LiteDto: ~50 Dateien (→ SimpleDto)
- SimpleDto-Tests: ~60 Tests (bleiben, müssen bestehen)
- LiteDto-Tests: 31 Tests (migrieren zu SimpleDto)
- Performance-Verbesserung: ~8.3x schneller (Standard), ~49.6x schneller (UltraFast-Ziel)

---

## 📝 Phase 1: Backup & Inventar

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [x] Abgeschlossen

### Ziel
Sicherstellen, dass wir alles wiederherstellen können, falls etwas schiefgeht.

### Schritte

- [ ] Git-Status prüfen (alles committed?)
- [ ] Neuen Branch erstellen: `refactor/litedto-becomes-simpledto`
- [ ] Backup-Tag erstellen: `backup-before-litedto-migration`
- [ ] Alle Tests laufen lassen (Baseline)

### Prompt für KI-Agent

```
Erstelle ein Backup vor der Migration:

1. Prüfe Git-Status: git status
2. Erstelle neuen Branch: git checkout -b refactor/litedto-becomes-simpledto
3. Erstelle Backup-Tag: git tag backup-before-litedto-migration
4. Führe alle Tests aus: docker exec data-helpers-php84 bash -c "vendor/bin/pest --no-output 2>&1; echo 'Exit code:' $?"
5. Dokumentiere Test-Ergebnisse in phase-1-baseline.md

Aktualisiere lite-to-simple.md:
- Markiere Phase 1 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 1 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- Neuer Branch erstellt
- Backup-Tag vorhanden
- Alle Tests bestehen (Baseline)
- `phase-1-baseline.md` mit Test-Ergebnissen

### Validierung
- [x] Branch existiert
- [x] Tag existiert
- [x] Alle wichtigen Tests bestehen
- [x] Exit code: 0

---

## 📝 Phase 2: SimpleDto-Klassen sichern (Backup durch Umbenennung)

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [x] Abgeschlossen

### Ziel
Alte SimpleDto-Implementierung als Backup umbenennen (nicht löschen!). Dient als Referenz und Vorlage für Code-Vergleiche während der Migration.

### ⚠️ WICHTIG: NICHT löschen, sondern umbenennen!

Die alte SimpleDto-Implementierung wird NICHT gelöscht, sondern nach `src/SimpleDto.bak/` umbenannt.
Dies dient als:
- **Backup** falls etwas schiefgeht
- **Referenz** für Code-Vergleiche
- **Vorlage** für die Migration

Die Tests in `tests/Unit/SimpleDto/` und `tests/Integration/SimpleDto/` bleiben unverändert!
Sie werden später verwendet, um zu validieren, dass das neue SimpleDto (basierend auf LiteDto) alle alten Tests besteht.

### Schritte

- [x] Benenne `src/SimpleDto/` um zu `src/SimpleDto.bak/`
- [x] **BEHALTE** alle Tests in `tests/Unit/SimpleDto/`
- [x] **BEHALTE** alle Tests in `tests/Integration/SimpleDto/`
- [x] Committe die Änderungen

### Prompt für KI-Agent

```
Sichere die alten SimpleDto-Klassen durch Umbenennung:

1. Benenne das gesamte Verzeichnis um:
   - mv src/SimpleDto src/SimpleDto.bak

2. Prüfe, dass das Backup existiert:
   - ls -la src/SimpleDto.bak/

3. BEHALTE alle Tests unverändert:
   - tests/Unit/SimpleDto/ bleibt unverändert
   - tests/Integration/SimpleDto/ bleibt unverändert

4. Committe die Änderungen:
   - git add src/SimpleDto.bak
   - git rm -r src/SimpleDto
   - git commit -m "Phase 2: Backup old SimpleDto to SimpleDto.bak"

Aktualisiere lite-to-simple.md:
- Markiere Phase 2 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 2 als "Abgeschlossen"
- Dokumentiere Backup-Pfad
```

### Erwartetes Ergebnis
- `src/SimpleDto.bak/` existiert mit allen alten Dateien
- `src/SimpleDto/` existiert nicht mehr
- Tests bleiben unverändert
- Git-Commit erstellt

### Validierung
- [x] `src/SimpleDto.bak/` existiert
- [x] `src/SimpleDto.bak/SimpleDto.php` existiert (als Teil des Backups)
- [x] `src/SimpleDto/` existiert nicht mehr (nur noch src/SimpleDto.php bleibt)
- [x] `tests/Unit/SimpleDto/` existiert noch
- [x] `tests/Integration/SimpleDto/` existiert noch
- [x] Git-Commit vorhanden

### 💡 Verwendung des Backups

Während der Migration kannst Du das Backup verwenden für:
- **Code-Vergleiche**: Wie war die alte Implementierung?
- **Feature-Check**: Welche Features hatte das alte SimpleDto?
- **Referenz**: Wie wurden bestimmte Dinge gelöst?

Das Backup wird erst in **Phase 9** (Finale Validierung) gelöscht, wenn alles funktioniert!

---

## 📝 Phase 3: LiteDto zu SimpleDto kopieren

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [x] Abgeschlossen

### Ziel
LiteDto-Dateien nach SimpleDto kopieren (noch ohne Umbenennung).

### Schritte

- [x] Kopiere `src/LiteDto/` nach `src/SimpleDto/` (rekursiv)
- [x] Behalte `src/LiteDto/` vorerst (für Referenz)
- [x] Committe die Änderungen

### Prompt für KI-Agent

```
Kopiere LiteDto nach SimpleDto:

1. Kopiere src/LiteDto/ nach src/SimpleDto/ (rekursiv, alle Dateien und Unterordner)
2. Behalte src/LiteDto/ vorerst (wird später gelöscht)
3. Prüfe, dass alle Dateien kopiert wurden
4. Committe: git commit -m "Phase 3: Copy LiteDto to SimpleDto"

Aktualisiere lite-to-simple.md:
- Markiere Phase 3 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 3 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- `src/SimpleDto/` enthält Kopie von LiteDto
- `src/LiteDto/` existiert noch
- Git-Commit erstellt

### Validierung
- [x] `src/SimpleDto/LiteDto.php` existiert
- [x] `src/SimpleDto/Support/LiteEngine.php` existiert
- [x] `src/LiteDto/` existiert noch
- [x] Git-Commit vorhanden

---

## 📝 Phase 4: Namespaces & Imports aktualisieren

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Alle Namespaces und Klassennamen von LiteDto zu SimpleDto umbenennen.

### Schritte

- [ ] Umbenennen in `src/SimpleDto/`:
  - `LiteDto.php` → `SimpleDto.php`
  - Klasse `LiteDto` → `SimpleDto`
  - `LiteDtoTrait.php` → `SimpleDtoTrait.php`
  - Trait `LiteDtoTrait` → `SimpleDtoTrait`
  - `Support/LiteEngine.php` → `Support/SimpleDtoEngine.php`
  - Klasse `LiteEngine` → `SimpleDtoEngine`
- [ ] Namespace ändern: `event4u\DataHelpers\LiteDto` → `event4u\DataHelpers\SimpleDto`
- [ ] Alle Imports in `src/SimpleDto/` aktualisieren
- [ ] Committe die Änderungen

### Prompt für KI-Agent

```
Benenne alle LiteDto-Klassen zu SimpleDto um:

1. In src/SimpleDto/:
   - Benenne LiteDto.php → SimpleDto.php um
   - Ändere Klassenname: class LiteDto → class SimpleDto
   - Benenne LiteDtoTrait.php → SimpleDtoTrait.php um
   - Ändere Trait-Name: trait LiteDtoTrait → trait SimpleDtoTrait
   - Benenne Support/LiteEngine.php → Support/SimpleDtoEngine.php um
   - Ändere Klassenname: class LiteEngine → class SimpleDtoEngine

2. Ändere alle Namespaces in src/SimpleDto/:
   - namespace event4u\DataHelpers\LiteDto → namespace event4u\DataHelpers\SimpleDto
   - namespace event4u\DataHelpers\LiteDto\Support → namespace event4u\DataHelpers\SimpleDto\Support

3. Aktualisiere alle Imports in src/SimpleDto/:
   - use event4u\DataHelpers\LiteDto\... → use event4u\DataHelpers\SimpleDto\...

4. Aktualisiere alle Referenzen innerhalb der Dateien:
   - LiteDto::class → SimpleDto::class
   - LiteEngine::class → SimpleDtoEngine::class
   - etc.

5. Committe: git commit -m "Phase 4: Rename LiteDto to SimpleDto (namespaces & classes)"

Aktualisiere lite-to-simple.md:
- Markiere Phase 4 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 4 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- `src/SimpleDto/SimpleDto.php` existiert
- `src/SimpleDto/SimpleDtoTrait.php` existiert
- `src/SimpleDto/Support/SimpleDtoEngine.php` existiert
- Alle Namespaces korrekt
- Git-Commit erstellt

### Validierung
- [ ] Keine `LiteDto` Klassennamen mehr in `src/SimpleDto/`
- [ ] Alle Namespaces sind `event4u\DataHelpers\SimpleDto`
- [ ] Keine `LiteEngine` Referenzen mehr
- [ ] Git-Commit vorhanden

---

## 📝 Phase 5: UltraFast als Standard implementieren

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Das aktuelle UltraFast-Verhalten wird zum Standard. #[UltraFast] Attribute wird optional/deprecated.

### Schritte

- [ ] In `SimpleDtoEngine.php`: UltraFast-Logik immer aktivieren
- [ ] #[UltraFast] Attribute als deprecated markieren (oder entfernen)
- [ ] Alle UltraFast-Optimierungen sind immer aktiv
- [ ] Tests anpassen (keine #[UltraFast] Attribute mehr nötig)
- [ ] Committe die Änderungen

### Prompt für KI-Agent

```
Mache UltraFast zum Standard:

1. Öffne src/SimpleDto/Support/SimpleDtoEngine.php
2. Finde alle Stellen, wo auf #[UltraFast] Attribute geprüft wird
3. Entferne die Prüfung - UltraFast-Verhalten ist immer aktiv
4. Markiere #[UltraFast] Attribute als @deprecated (oder entferne es komplett)
5. Aktualisiere Tests: Entferne #[UltraFast] Attribute aus Test-DTOs (optional)
6. Führe Tests aus: docker exec data-helpers-php84 bash -c "vendor/bin/pest tests/Unit/SimpleDto/ --compact"
7. Committe: git commit -m "Phase 5: Make UltraFast the default behavior"

Aktualisiere lite-to-simple.md:
- Markiere Phase 5 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 5 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- UltraFast-Verhalten ist immer aktiv
- #[UltraFast] Attribute deprecated oder entfernt
- Tests bestehen
- Git-Commit erstellt

### Validierung
- [ ] Keine Prüfung auf #[UltraFast] Attribute mehr
- [ ] Tests bestehen
- [ ] Performance gleich oder besser
- [ ] Git-Commit vorhanden

---

## 📝 Phase 6: Neues Super-UltraFast Mode (~0.5μs)

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Implementiere ein noch schnelleres Super-UltraFast Mode mit Ziel ~0.5μs oder weniger.

### Optimierungs-Strategien

1. **Aggressive Caching**:
   - Cache compiled property maps
   - Cache validation rules
   - Cache cast configurations

2. **Code Generation**:
   - Generate optimized accessor methods
   - Pre-compile validation logic
   - Generate optimized toArray() methods

3. **Lazy Initialization**:
   - Defer expensive operations
   - Only compute when needed

4. **Memory Optimization**:
   - Reduce object allocations
   - Reuse objects where possible

### Schritte

- [ ] Analysiere aktuelle Performance-Bottlenecks
- [ ] Implementiere aggressive Caching-Strategien
- [ ] Implementiere Code-Generation (optional)
- [ ] Implementiere Lazy Initialization
- [ ] Benchmark: Ziel ~0.5μs erreichen
- [ ] Tests validieren
- [ ] Committe die Änderungen

### Prompt für KI-Agent

```
Implementiere Super-UltraFast Mode mit Ziel ~0.5μs:

1. Analysiere aktuelle Performance:
   - Führe Benchmark aus: docker exec data-helpers-php84 bash -c "php scripts/comprehensive-benchmark.php"
   - Identifiziere Bottlenecks

2. Implementiere Optimierungen:
   - Aggressive Caching für Property Maps
   - Aggressive Caching für Validation Rules
   - Aggressive Caching für Cast Configurations
   - Lazy Initialization wo möglich
   - Reduziere Reflection-Calls

3. Benchmark nach jeder Optimierung:
   - Messe Performance-Verbesserung
   - Dokumentiere Ergebnisse

4. Validiere Tests:
   - docker exec data-helpers-php84 bash -c "vendor/bin/pest tests/Unit/SimpleDto/ --compact"

5. Committe: git commit -m "Phase 6: Implement Super-UltraFast Mode (~0.5μs)"

Aktualisiere lite-to-simple.md:
- Markiere Phase 6 als "In Arbeit"
- Dokumentiere Performance-Verbesserungen
- Wenn abgeschlossen: Markiere Phase 6 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- Performance ~0.5μs oder besser
- Alle Tests bestehen
- Dokumentierte Performance-Verbesserungen
- Git-Commit erstellt

### Validierung
- [ ] Performance-Ziel erreicht (~0.5μs)
- [ ] Alle Tests bestehen
- [ ] Keine Regressions
- [ ] Git-Commit vorhanden

---

## 📝 Phase 7: Tests migrieren & validieren

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Alle LiteDto-Tests zu SimpleDto migrieren und sicherstellen, dass alte SimpleDto-Tests auch funktionieren.

### Schritte

- [ ] Kopiere Tests von `tests/Unit/LiteDto/` nach `tests/Unit/SimpleDto/`
- [ ] Kopiere Tests von `tests/Integration/LiteDto/` nach `tests/Integration/SimpleDto/`
- [ ] Aktualisiere Namespaces in kopierten Tests
- [ ] Aktualisiere Klassennamen (LiteDto → SimpleDto)
- [ ] Führe alle SimpleDto-Tests aus
- [ ] Behebe fehlgeschlagene Tests
- [ ] Committe die Änderungen

### Prompt für KI-Agent

```
Migriere LiteDto-Tests zu SimpleDto:

1. Kopiere Tests:
   - tests/Unit/LiteDto/* → tests/Unit/SimpleDto/
   - tests/Integration/LiteDto/* → tests/Integration/SimpleDto/

2. Aktualisiere in kopierten Tests:
   - use event4u\DataHelpers\LiteDto\... → use event4u\DataHelpers\SimpleDto\...
   - LiteDto::class → SimpleDto::class
   - extends LiteDto → extends SimpleDto
   - etc.

3. Führe alle SimpleDto-Tests aus:
   - docker exec data-helpers-php84 bash -c "vendor/bin/pest tests/Unit/SimpleDto/ --compact"
   - docker exec data-helpers-php84 bash -c "vendor/bin/pest tests/Integration/SimpleDto/ --compact"

4. Behebe fehlgeschlagene Tests (falls vorhanden)

5. Committe: git commit -m "Phase 7: Migrate LiteDto tests to SimpleDto"

Aktualisiere lite-to-simple.md:
- Markiere Phase 7 als "In Arbeit"
- Dokumentiere Test-Ergebnisse
- Wenn abgeschlossen: Markiere Phase 7 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- Alle LiteDto-Tests kopiert und angepasst
- Alle SimpleDto-Tests bestehen
- Git-Commit erstellt

### Validierung
- [ ] Alle Tests in `tests/Unit/SimpleDto/` bestehen
- [ ] Alle Tests in `tests/Integration/SimpleDto/` bestehen
- [ ] Exit code: 0
- [ ] Git-Commit vorhanden

---

## 📝 Phase 8: Dokumentation aktualisieren

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Alle Dokumentation von LiteDto zu SimpleDto migrieren und aktualisieren.

### Schritte

- [ ] Migriere `starlight/src/content/docs/lite-dto/` → `starlight/src/content/docs/simple-dto/`
- [ ] Aktualisiere `starlight/astro.config.mjs` (LiteDto → SimpleDto)
- [ ] Aktualisiere `starlight/src/content/docs/main-classes/dto-comparison.md`
- [ ] Entferne LiteDto-Spalten (nur noch SimpleDto)
- [ ] Aktualisiere `scripts/comprehensive-benchmark.php`
- [ ] Aktualisiere `README.md`
- [ ] Committe die Änderungen

### Prompt für KI-Agent

```
Aktualisiere die Dokumentation:

1. Migriere Dokumentation:
   - Kopiere starlight/src/content/docs/lite-dto/ → starlight/src/content/docs/simple-dto/
   - Aktualisiere alle Inhalte: LiteDto → SimpleDto
   - Aktualisiere Code-Beispiele

2. Aktualisiere starlight/astro.config.mjs:
   - Ändere "LiteDto" Section zu "SimpleDto"
   - Aktualisiere alle Links

3. Aktualisiere dto-comparison.md:
   - Entferne LiteDto-Spalten
   - Behalte nur SimpleDto-Spalten
   - Aktualisiere Performance-Zahlen

4. Aktualisiere scripts/comprehensive-benchmark.php:
   - Entferne LiteDto-Benchmarks
   - Aktualisiere SimpleDto-Benchmarks

5. Aktualisiere README.md:
   - Entferne LiteDto-Referenzen
   - Aktualisiere Beispiele

6. Committe: git commit -m "Phase 8: Update documentation (LiteDto → SimpleDto)"

Aktualisiere lite-to-simple.md:
- Markiere Phase 8 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 8 als "Abgeschlossen"
```

### Erwartetes Ergebnis
- Dokumentation vollständig migriert
- Keine LiteDto-Referenzen mehr
- Git-Commit erstellt

### Validierung
- [ ] `starlight/src/content/docs/simple-dto/` existiert
- [ ] Keine LiteDto-Referenzen in Dokumentation
- [ ] dto-comparison.md aktualisiert
- [ ] Git-Commit vorhanden

---

## 📝 Phase 9: Finale Validierung & Cleanup

**Status**: [ ] Nicht gestartet | [ ] In Arbeit | [ ] Abgeschlossen

### Ziel
Finale Validierung, Cleanup und Abschluss der Migration. Alte Backups werden gelöscht.

### Schritte

- [ ] Lösche `src/LiteDto/` (nicht mehr benötigt)
- [ ] Lösche `src/SimpleDto.bak/` (Backup nicht mehr benötigt)
- [ ] Lösche `tests/Unit/LiteDto/` (migriert zu SimpleDto)
- [ ] Lösche `tests/Integration/LiteDto/` (migriert zu SimpleDto)
- [ ] Führe ALLE Tests aus
- [ ] Führe Benchmark aus
- [ ] Validiere Performance-Ziele
- [ ] Validiere Dokumentation
- [ ] Erstelle finalen Commit
- [ ] Erstelle Tag: `litedto-to-simpledto-complete`

### Prompt für KI-Agent

```
Finale Validierung und Cleanup:

1. Führe ALLE Tests aus (vor dem Löschen!):
   - docker exec data-helpers-php84 bash -c "vendor/bin/pest --no-output 2>&1; echo 'Exit code:' $?"

2. Führe Benchmark aus:
   - docker exec data-helpers-php84 bash -c "php scripts/comprehensive-benchmark.php"

3. Validiere Performance-Ziele:
   - SimpleDto (Standard): ~3.0μs (aktuelles UltraFast-Niveau)
   - SimpleDto #[UltraFast]: ~0.5μs oder besser (Ziel erreicht?)
   - Dokumentiere finale Performance-Zahlen

4. Validiere Dokumentation:
   - Keine LiteDto-Referenzen mehr
   - Alle Links funktionieren
   - Beispiele korrekt

5. Wenn alles OK: Lösche alte Dateien:
   - rm -rf src/LiteDto/
   - rm -rf src/SimpleDto.bak/
   - rm -rf tests/Unit/LiteDto/
   - rm -rf tests/Integration/LiteDto/

6. Erstelle finale Commits:
   - git add -A
   - git commit -m "Phase 9: Remove old LiteDto and SimpleDto.bak files"
   - git tag litedto-to-simpledto-complete

7. Erstelle Migrations-Report in phase-9-final-report.md mit:
   - Performance-Vergleich (vorher/nachher)
   - Test-Ergebnisse
   - Gelöschte Dateien
   - Erreichte Ziele

Aktualisiere lite-to-simple.md:
- Markiere Phase 9 als "In Arbeit"
- Wenn abgeschlossen: Markiere Phase 9 als "Abgeschlossen"
- Markiere ALLE Phasen als abgeschlossen
```

### Erwartetes Ergebnis
- Keine LiteDto-Dateien mehr
- Keine SimpleDto.bak-Dateien mehr
- Alle Tests bestehen
- Performance-Ziele erreicht
- Dokumentation vollständig
- Git-Tag erstellt
- `phase-9-final-report.md` mit Zusammenfassung

### Validierung
- [ ] `src/LiteDto/` existiert nicht mehr
- [ ] `src/SimpleDto.bak/` existiert nicht mehr
- [ ] `tests/Unit/LiteDto/` existiert nicht mehr
- [ ] `tests/Integration/LiteDto/` existiert nicht mehr
- [ ] Alle Tests bestehen (Exit code: 0)
- [ ] Performance-Ziel erreicht (~0.5μs für UltraFast)
- [ ] Dokumentation vollständig
- [ ] Git-Tag vorhanden

### ⚠️ Wichtig: Backup-Löschung

Das Backup `src/SimpleDto.bak/` wird erst JETZT gelöscht, nachdem:
- ✅ Alle Tests bestehen
- ✅ Performance-Ziele erreicht
- ✅ Dokumentation vollständig
- ✅ Alles funktioniert

Falls Du das Backup noch behalten möchtest, überspringe den Lösch-Schritt!

---

## ✅ Finale Checkliste

### Code
- [ ] `src/SimpleDto/` existiert und funktioniert
- [ ] `src/LiteDto/` existiert nicht mehr
- [ ] `src/SimpleDto.bak/` existiert nicht mehr
- [ ] Alle Namespaces korrekt
- [ ] Alle Imports korrekt

### Tests
- [ ] Alle Tests in `tests/Unit/SimpleDto/` bestehen
- [ ] Alle Tests in `tests/Integration/SimpleDto/` bestehen
- [ ] `tests/Unit/LiteDto/` existiert nicht mehr
- [ ] `tests/Integration/LiteDto/` existiert nicht mehr
- [ ] Exit code: 0

### Performance
- [ ] SimpleDto: ~0.5μs oder besser
- [ ] Benchmark dokumentiert
- [ ] Performance-Ziele erreicht

### Dokumentation
- [ ] `starlight/src/content/docs/simple-dto/` vollständig
- [ ] `starlight/src/content/docs/lite-dto/` existiert nicht mehr
- [ ] `dto-comparison.md` aktualisiert
- [ ] `README.md` aktualisiert
- [ ] Keine LiteDto-Referenzen mehr

### Git
- [ ] Branch: `refactor/litedto-becomes-simpledto`
- [ ] Backup-Tag: `backup-before-litedto-migration`
- [ ] Finale-Tag: `litedto-to-simpledto-complete`
- [ ] Alle Änderungen committed

---

## 🚨 Wichtige Hinweise

### Nach jedem Schritt

**WICHTIG**: Nach JEDEM abgeschlossenen Schritt muss `lite-to-simple.md` aktualisiert werden:

1. Markiere den aktuellen Schritt als "In Arbeit" (wenn gestartet)
2. Markiere den aktuellen Schritt als "Abgeschlossen" (wenn fertig)
3. Dokumentiere wichtige Erkenntnisse
4. Aktualisiere "Aktueller Status" am Anfang der Datei

### Performance-Ziele

- **Aktuelles SimpleDto**: ~24.8μs
- **Aktuelles LiteDto**: ~4.8μs
- **Aktuelles LiteDto #[UltraFast]**: ~3.0μs
- **Ziel neues SimpleDto (Standard)**: ~3.0μs (aktuelles UltraFast)
- **Ziel neues SimpleDto #[UltraFast]**: ~0.5μs oder besser

### Test-Anforderungen

- Alle Tests müssen bestehen (Exit code: 0)
- Keine Regressions
- Alte SimpleDto-Tests müssen mit neuem SimpleDto funktionieren
- LiteDto-Tests müssen mit neuem SimpleDto funktionieren

### Rollback-Plan

Falls etwas schiefgeht:

```bash
# Zurück zum Backup
git checkout backup-before-litedto-migration

# Oder Branch löschen und neu starten
git checkout main
git branch -D refactor/litedto-becomes-simpledto
git checkout -b refactor/litedto-becomes-simpledto
```

---

## 📞 Kontakt & Hilfe

Bei Fragen oder Problemen:
- Prüfe Git-History: `git log --oneline`
- Prüfe Git-Status: `git status`
- Prüfe aktuelle Phase in dieser Datei
- Führe Tests aus: `docker exec data-helpers-php84 bash -c "vendor/bin/pest --compact"`

---

**Letzte Aktualisierung**: 2025-10-31
**Status**: Vorbereitung
**Nächster Schritt**: Phase 0 starten

