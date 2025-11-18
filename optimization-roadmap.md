# Optimization Roadmap – event4u/data-helpers

_Status: Draft – to be refined and updated continuously_

Dieses Dokument beschreibt eine mehrstufige Optimierungs-Roadmap für das `event4u/data-helpers` Paket.
Sie deckt Code-Struktur, Objekt-Orientierung, Performance, Caching, Tests und Dokumentation ab.

Die Roadmap ist so aufgebaut, dass jede Phase von einer KI / einem Agenten selbständig
bearbeitet werden kann.

---

## Global Rules for All Agents / KIs

**Wichtige Grundregeln (bitte IMMER beachten):**

- [ ] Vor JEDEM Lauf dieses Dokuments **`optimization-roadmap.md` vollständig einlesen**.
- [ ] Vor JEDEM Lauf den relevanten **Code, Beispiele und Tests direkt von der Disk lesen**.
  - Nutze keine in‑Memory-Repräsentationen aus früheren Läufen.
  - Gehe immer davon aus, dass der User (Matze) Änderungen vorgenommen haben kann.
- [ ] Niemals Commits oder Pushes ausführen – das macht ausschließlich Matze.
- [ ] Halte Dich an die Coding-Guidelines und Style-Rules (ECS, Rector, PHPStan‑Level) des Projekts.
- [ ] Verwende die bestehenden Taskfile-Kommandos für Tests & Quality (siehe Checkliste nach jeder Phase).
- [ ] Sei konservativ mit Refactorings: Saubere, kleine Schritte mit Tests nach jedem Schritt.

**Nach JEDER Phase (Checkliste):**

- [ ] `task test:run` – alle Fehler beheben.
- [ ] `task test:plain` – alle Fehler beheben.
- [ ] `task test:e2e:laravel` – alle Fehler beheben.
- [ ] `task test:e2e:symfony` – alle Fehler beheben.
- [ ] `task quality:phpstan` – alle Fehler beheben (nicht in Baseline verschieben oder ignorieren, nur wenn gar nicht anders möglich inline ignorieren).
- [ ] `task quality:refactor:fix` – ausführen und Dateien finalisieren.

> **Hinweis:** Wenn eine Phase nur teilweise umgesetzt wird, sollten zumindest alle
> betroffenen Bereiche mit den oben genannten Commands geprüft werden.

---

## Roadmap-Notation

- `[ ]` – Task noch offen
- `[-]` – Task wird bewusst geskipped (z. B. nicht mehr relevant)
- `[x]` – Task erledigt

Beim Arbeiten an der Roadmap Tasks immer aktualisieren – sie ist das zentrale Steuerungsdokument.

---

## High-Level Überblick über Optimierungsziele

1. **Bessere Code-Strukturen & Architektur**
   - Klare Trennung von Kernlogik, Support/Helpern, Framework-Integrationen und Doku-Beispielen.
   - Weniger duplizierter Code, mehr Wiederverwendung, klare öffentliche API.
2. **Mehr Objekt-Orientierung**
   - Wo sinnvoll, strukturiert OO-Design statt verstreuter Utility-Funktionen.
   - Explizite Domänen-Objekte für wiederkehrende Konzepte (Mapping-Optionen, Cache‑Strategien, Pipelines, etc.).
3. **Mehr Geschwindigkeit**
   - Hotspots identifizieren (DataAccessor, DataMapper, SimpleDto, LiteDto) und gezielt optimieren.
   - Reflection, Parsing und Normalisierungen cachen oder reduzieren.
4. **Besseres Caching**
   - SimpleDto- und Mapping-Caches konsistent nutzen und optimieren.
   - Cache-Warming, Invalidierungsstrategien und Dev/Prod-Modi sauber trennen.
5. **Mehr und bessere Tests**
   - Testabdeckung für Edge-Cases und Performance-Kritikpunkte erhöhen.
   - E2E-/Integrationstests für Laravel und Symfony ausbauen/absichern.

---

## Phase 0 – Bestandsaufnahme & Baseline

Ziel: Klarer Überblick über aktuellen Stand, Hotspots und Metriken.

- [x] Alle relevanten Dokumentationsseiten von `http://localhost:4321/data-helpers/` (bzw. deren Markdown-Quellen
      unter `starlight/src/content/docs`) einlesen – insbesondere Getting Started, Core Concepts, Main Classes,
      Performance, Caching, Testing, Framework Integration, Advanced, Examples, Attributes, Helpers,
      Troubleshooting, Guides und API Reference.
- [x] Projektstruktur analysieren (src, tests, tests-e2e, examples, starlight, scripts, config, Taskfile).
- [x] Öffentliche API identifizieren (Kernklassen wie `DataAccessor`, `DataMapper`, `DataFilter`, `DataMutator`,
      `SimpleDto`, `LiteDto`, Helper, Framework-Integrationen).
- [x] Bestehende Caching-Mechanismen dokumentieren (z. B. SimpleDto-Cache, PathParsingCache, ConfigHelper,
      CacheManager/CacheInvalidation/CacheDriver, Reflection- und Attribute-Caches).
- [x] Bestehende Benchmarks und Performance-Dokumentation sichten (`benchmarks/`, `scripts/*benchmark*`,
      Starlight-Performance-Doku).
- [x] Testlandschaft sichten (`tests/Unit`, `tests/Integration`, `tests-e2e`, `tests/Documentation`, `examples`).
- [x] Aktuelle Test- und Quality-Baseline erstellen (ohne Änderungen, nur Status dokumentieren):
  - Alle Kern-Test-Suites grün (`task test:run`, `task test:plain`, `task test:e2e:laravel`, `task test:e2e:symfony`).
  - PHPStan ohne Fehler (`task quality:phpstan`).
  - Refactor-Fixes ohne nötige Änderungen (`task quality:refactor:fix`).

Am Ende von Phase 0:

- [x] Kurze Zusammenfassung der Architektur und der wichtigsten Komponenten (in einem separaten MD-Dokument
      oder Abschnitt in dieser Datei festhalten).
- [x] Liste der größten „Pain Points“: Struktur, Lesbarkeit, Performance, Caching, Tests.
- [x] Checkliste „After every phase“ einmal komplett ausführen und Ergebnisse notieren.

### Phase 0 – Zusammenfassung (Stand: 2025-11-18)

**Architektur & öffentliche API (Kurzüberblick):**

- Kernklassen: `DataAccessor`, `DataMutator`, `DataMapper`, `DataFilter`, `DataCollection` als zentrale
  Bausteine für Lesen/Schreiben/Transformieren von Daten mit Dot-Notation & Wildcards.
- DTO-Schicht: `SimpleDto` (reichhaltig, validierungslastig, viele Traits) und `LiteDto` (minimalistisch,
  performance-orientiert) mit Attribut-basierter Konfiguration und tiefen Framework-Integrationen
  (Laravel, Symfony, Doctrine).
- Support-/Helper-Schicht: `Helpers/*` (insb. `ConfigHelper`, `EnvHelper`, `DotPathHelper`, `MathHelper`,
  `ObjectHelper`) plus `Support/*` (u. a. `ReflectionCache`, `ArrayableHelper`, `CollectionHelper`, `EntityHelper`,
  `Lazy`, `Optional`, Date/String-Helfer).
- Caching: Zentrale Konfiguration über `config/data-helpers.php` und `DataHelpersConfig`,
  Implementierung über `Support/Cache/*` (CacheManager, CacheInvalidator, CacheAdapter für Filesystem,
  Laravel, Symfony, Attribute-/Validation-Cache, `CastInstancePool`, `PathParsingCache`).
- Logging: `Logging/*` mit konfigurierbaren Treibern (Filesystem, Framework, Grafana/Loki/Prometheus) und
  feingranularen Events (Mapping-/Query-/Validation-/Performance-Events).
- Framework-Integrationen: `Frameworks/Laravel/*`, `Frameworks/Symfony/*`, Doctrine-Integration sowie
  komfortable E2E-Beispiele in `tests-e2e/*`.

**Tests & Benchmarks:**

- ~4300+ Tests, inklusive:
  - Unit- und Integrationstests für Kernklassen und Dto-Verhalten.
  - Dokumentations-Validierung (z. B. `StarlightAllExamplesTest`) für Starlight-Beispiele.
  - E2E-Tests für Laravel und Symfony (komplette Mini-Apps mit Dtos, Requests, Validation, Cache-Warming).
- Umfassende Benchmarks (`benchmarks/*`, `scripts/*benchmark*`, Performance-Doku) für DataAccessor,
  DataMutator, DataMapper, SimpleDto/LiteDto und externe Vergleiche.

**Identifizierte (vorläufige) Pain Points für spätere Phasen:**

- Komplexität von `SimpleDto`/`LiteDto` durch viele Traits und Attribute-Kombinationen – hier lohnt sich
  eine noch klarere Modularisierung und evtl. Vereinheitlichung von Pipelines/Caching.
- DataMapper-API (`MappingFacade`, `MappingEngine`, `MappingOptions`) nutzt teilweise noch boolsche Flags
  und komplexe Kontrollflüsse – Kandidat für stärkere OO-Option-Objekte und klarere Pipelines.
- Dot-Path-/Wildcard-Handling ist zwar zentralisiert (`DotPathHelper`, `PathParsingCache`), aber die Nutzung
  der Helfer ist nicht in allen Komponenten 100 % einheitlich – hier sind Konsolidierung & weitere
  Wiederverwendung denkbar.
- Viele E2E-Tests (insb. Symfony) laufen als „risky“ (ohne explizite Assertions), obwohl sie durchlaufen –
  Potenzial, die Aussagekraft dieser Tests in späteren Phasen zu erhöhen.
- Logging-/Monitoring-Konfiguration ist sehr mächtig, aber aktuell eher generisch dokumentiert – spätere
  Phasen können hier noch Best-Practices & gängige Presets herausarbeiten.

---

## Phase 1 – Code-Struktur & Modularisierung

Ziel: Klare Struktur, verständliche Pakete/Module und weniger duplizierter Code.

- [x] Package-Struktur prüfen (Kern vs. Framework-Integration vs. Helpers vs. Support/Internals).
- [x] Doppelte oder ähnliche Logik identifizieren (z. B. wiederkehrende Pfad-Parsing-, Casting-, Mapping-,
      Collection- oder Entity-Access-Muster).
- [x] Gemeinsame Abstraktionen/Interfaces identifizieren und wo möglich bestehende Helfer nutzen (z. B.
      zentrale Nutzung von `PathParsingCache` statt eigenem `explode('.', $path)`).
- [x] Public API vs. Internals grob trennen (Namespacestufen & Einordnung in dieser Roadmap dokumentiert).
- [x] Support-/Helper-Klassen sichten und grob gruppieren (Array-/Object-/Entity-Helfer, Config-/Cache-Helfer,
      Framework-spezifische Integration).
- [-] Dokumentation aktualisieren, falls der öffentliche Einstiegspunkt / Namespaces sich ändern (derzeit keine
      BC-relevanten Änderungen vorgenommen, daher nicht erforderlich).

Besonderer Fokus:

- [x] `src/DataAccessor.php` – Struktur des Pfad-Parsing und der Extraktion sichten (bestehendes Design
      mit `PathParsingCache`/`DotPathHelper` übernommen).
- [x] `src/DataMapper/*` – MappingEngine, MappingFacade, Hooks, Pipelines grob analysiert (keine großen
      Umbauten, aber Konsolidierung beim Dot-Path-Parsing vorgenommen).
- [-] `src/SimpleDto/*` & `src/LiteDto/*` – Konsistenz der DTO-APIs (für spätere, größere Refactoring-Runden
      vorgemerkt, derzeit nur analysiert, aber bewusst nicht geändert).
- [x] `src/Support/*` & `src/Helpers/*` – Helfer gesichtet und grob in funktionale Gruppen eingeordnet.

Ergebnis von Phase 1:

- [x] Klar dokumentierte Modul-/Namespace-Struktur (siehe Architektur-Abschnitt in Phase 0
      und das Package-Mapping in dieser Roadmap).
- [x] Reduzierte Code-Duplikation an einer kritischen Stelle (Dot-Path-Parsing im DataMapper nutzt jetzt
      dieselbe Infrastruktur wie DataAccessor/DataMutator via `PathParsingCache`).
- [x] Keine BC-Breaks ohne explizite Dokumentation/Changelog-Hinweise (nur interne Konsolidierungen,
      keine öffentliche API geändert).

### Phase 1 – Zwischenstand (Stand: 2025-11-18)

- Dot-Path-Parsing im DataMapper (`FluentDataMapper::getValueFromPath`) auf `PathParsingCache` umgestellt,
  um die zentrale Pfad-Parsing-Infrastruktur zu nutzen und doppelte Logik zu vermeiden.


---

## Phase 2 – Mehr Objekt-Orientierung & API-Ergonomie

Ziel: OO-Design stärken, Interfaces präzisieren und APIs ergonomischer machen.

- [x] Wiederkehrende „Options“-Arrays an zentralen Stellen in explizite Value Objects / DTOs
      überführen (erste Iteration: `MappingOptions::fromLegacy()` + Nutzung im DataMapper;
      weitere Kandidaten wie Cache-Optionen folgen in späteren Runden).
- [x] Pipeline-/Hook-Konzepte in klar definierte Interfaces gießen (MapperHookInterface +
      ContextHookInterface/ValueHookInterface/TargetHookInterface + `PipelineStepInterface`).
- [x] Fluent APIs konsistent gestalten (z. B. `DataMapper::from()->template()->pipeline()->map()->getTarget()`).
- [x] Sinnvolle Typ-Hints und PHPDoc-Annotations ergänzen, um PHPStan Level 9 sauber zu halten.
- [x] Stellen identifizieren, an denen mehr Polymorphie statt `switch`/`if`-Ketten sinnvoll ist.

Besondere Kandidaten:

- [x] DataMapper (MappingDefinitionen, Hooks, Pipelines, Options).
- [x] SimpleDto & LiteDto (Attribut-basierte Konfiguration, Caster, Validatoren).
- [x] Caching-Strategien (Enum + passende Strategie-Objekte, wo sinnvoll).

### Phase 2 – Zwischenstand (Stand: 2025-11-18)

- `MappingOptions` als zentrales Value Object für Mapping-Konfiguration gefestigt und um
  `fromLegacy()` erweitert, um die bestehende `MappingFacade`-Signatur (bool|MappingOptions +
  Einzelparameter) OO-konform zu kapseln.
- `MappingFacade::map()` nutzt nun konsequent `MappingOptions::fromLegacy()` statt die Optionen
  lokal auseinander zu ziehen; damit ist die Konfiguration für DataMapper-Operationen klarer
  an einem Ort gebündelt.
- Hook-System weiter formalisiert: neue Interfaces (MapperHookInterface, ContextHookInterface,
  ValueHookInterface, TargetHookInterface) sowie PipelineStepInterface für Filter/Pipelines
  eingeführt, ohne bestehende Callback-basierte APIs zu brechen.
- Fluent API in der DataMapper-Doku vereinheitlicht: `DataMapper::from($source)` als kanonischer Einstieg
  (Alias zu `DataMapper::source($source)`); Standard-Kette `from()->template()->pipeline()->map()->getTarget()`
  ist explizit dokumentiert.
- PHPStan-Level 9 aktuell ohne Fehler (vollständiger Lauf über das Projekt), zentrale Typ-Hints und PHPDocs
  für DataMapper/MappingOptions/MappingFacade/DTOs sind ergänzt und konsistent gehalten.
- Polymorphismus-Kandidaten geprüft: DataMapper, SimpleDto/LiteDto und das Cache-Subsystem nutzen bereits
  Enums, Value Objects und Adapter-Klassen; es gibt keine großen `switch`/`if`-Ketten mehr, bei denen eine
  zusätzliche Strategie-Hierarchie aktuell einen klaren Mehrwert bringen würde.




Ergebnis von Phase 2:

- [ ] Klarere, OO-getriebene Struktur für Konfiguration und Pipelines.
- [ ] Bessere Erweiterbarkeit für zukünftige Features (weniger „harte“ if/else-Verzweigungen).

---

## Phase 3 – Performance-Optimierungen

Ziel: Hotspots identifizieren und gezielt beschleunigen.

- [ ] Bestehende Benchmarks (`benchmarks/*.php`, `scripts/*benchmark*`) analysieren und ggf. erweitern.
- [ ] DataAccessor-Hotspots optimieren (Pfad-Parsing, Wildcard-Handling, Generators vs. Arrays).
  - [x] Wildcard-Handling: `WildcardHandler::normalizeWildcardArray()` auf Single-Pass-Variante umgestellt (2–3 % schneller für Wildcard-Normalisierung laut `DataAccessorBench`, siehe `hotspot.md` H2).
- [ ] DataMapper-Performance prüfen (MappingEngine, Flattening, Hooks, Pipelines, Exception-Erfassung).
  - [x] Wildcard-Mapping optimiert (`MappingEngine::processWildcardMapping` + per-Item-Writes in `DataMutator`), siehe `hotspot.md` H1.
  - [x] Deep AutoMap: Shape-Only-Modus für `AutoMappingEngine::flattenSourcePaths()` eingeführt und in Deep-AutoMap verkabelt (H3), Benchmarks laut `hotspot.md`.
- [ ] SimpleDto/LiteDto-Overhead minimieren (Reflection, Attribute-Lookups, Caster-Instanziierung).
- [ ] Micro-Optimierungen nur dort durchführen, wo sie messbar sind (Benchmark vorher/nachher).
- [ ] Wenn sinnvoll, zusätzliche Benchmarks für Real-World-Szenarien (große Payloads, tiefe Strukturen) ergänzen.

Ergebnis von Phase 3:

- [ ] Dokumentierte Performance-Gewinne pro Subsystem (DataAccessor, DataMapper, DTOs).
- [ ] Benchmarks in `benchmarks/` laufen grün und reproduzierbar.

---

## Phase 4 – Caching-Strategien & Cache-Warming

Ziel: Caching konsequent nutzen und klar zwischen Dev- und Prod-Szenarien unterscheiden.

- [ ] Bestehende Cache-Mechanismen sichten (z. B. SimpleDto-Caching, `PathParsingCache`, Config-basierte
      Invalidierungsstrategien – `CacheInvalidation::MANUAL`, `MTIME`, etc.).
- [ ] Sicherstellen, dass Cache-Konfiguration zentral über `config/data-helpers.php` bzw. ConfigHelper läuft.
- [ ] Cache-Warming-Skripte (z. B. `bin/warm-cache.php`) prüfen, optimieren und dokumentieren.
- [ ] Saubere Trennung zwischen Dev- und Prod-Modus (Validierung/MTIME vs. MANUAL-Strategie).
- [ ] Caching-Strategien in Doku-Seiten (Starlight) klar erläutern, inklusive Trade-offs.

Ergebnis von Phase 4:

- [ ] Klar definierte und getestete Cache-Strategien inkl. Deployment-Empfehlungen.
- [ ] Minimale Runtime-Overheads im Prod-Betrieb durch konsequent genutzte Caches.

---

## Phase 5 – Tests & Qualitätssicherung

Ziel: Testabdeckung erhöhen, E2E-Szenarien absichern und Qualität konsistent halten.

- [ ] Unit-Tests für alle Kernklassen nachschärfen (Edge-Cases, Fehlerpfade, Typ-Mismatches).
- [ ] Integrationstests für komplexe Szenarien (z. B. kombinierter Einsatz von DataAccessor, DataMapper,
      SimpleDto/LiteDto in realistischen Payloads).
- [ ] E2E-Tests für Laravel und Symfony ergänzen/absichern (Ressourcen, Requests, Pipes, Middlewares).
- [ ] Tests für Caching-Verhalten (Invalidierungsstrategien, Cache-Warming, Manual vs. MTIME).
- [ ] Tests für Performance-Regressionen (z. B. einfache Smoke-Benchmarks oder Zeitvergleiche innerhalb
      vernünftiger Toleranzen).
- [ ] Sicherstellen, dass PHPStan, ECS und Rector sauber durchlaufen.

Ergebnis von Phase 5:

- [ ] Hohe Testabdeckung mit Fokus auf Kernfeatures und Integrationen.
- [ ] Klarer, automatisierter Quality-Gate (Tasks im Taskfile) für zukünftige Änderungen.

---

## Phase 6 – Dokumentation & Beispiele synchronisieren

Ziel: Doku, Beispiele und API in Einklang bringen, damit Nutzer alle Optimierungen verstehen und nutzen können.

- [ ] Starlight-Dokumentation (`starlight/src/content/docs`) mit tatsächlicher API und neuen Strukturen
      synchronisieren (SimpleDto/LiteDto, DataMapper, Caching, Performance, Framework-Integration).
- [ ] Beispiele in `examples/` aktualisieren und ggf. konsolidieren (redundante Beispiele entfernen,
      fehlende Szenarien ergänzen).
- [ ] Doku-Abschnitte zu Performance-Tuning, Caching, Best Practices erweitern.
- [ ] Troubleshooting- und Guides-Sektionen mit den neuen Erkenntnissen/Patterns ergänzen.

Ergebnis von Phase 6:

- [ ] Konsistente, aktuelle Dokumentation.
- [ ] Beispiele, die die empfohlenen Patterns und Optimierungen zeigen.

---

## Phase 7 – Aufräumen & Feinschliff

Ziel: Kleine Unsauberkeiten entfernen, API-Kanten glätten und alles für Release/Tag vorbereiten.

- [ ] Offene TODOs/`@todo`/`@deprecated`-Stellen prüfen und bereinigen.
- [ ] Naming-Inkonsistenzen und kleine API-Kanten ausbessern (ohne unnötige BC-Breaks).
- [ ] Changelog/Release Notes vorbereiten (außerhalb dieser Datei, z. B. `CHANGELOG.md`).
- [ ] Letzte komplette Runde der „After every phase“-Checkliste.

Ergebnis von Phase 7:

- [ ] Projekt ist stabil, schnell, gut getestet und sauber dokumentiert.

---

## Agent Prompt / Summary

> **Verpflichtender Prompt für jede KI / jeden Agenten, der an diesem Projekt arbeitet:**
>
> 1. Lies **vor JEDEM Lauf** diese Datei `optimization-roadmap.md` vollständig.
> 2. Lies **vor JEDEM Lauf** alle relevanten Dateien (Code, Tests, Beispiele, Konfiguration) direkt von der Disk
>    und verlasse Dich nicht auf in‑Memory- oder frühere Kontexte.
> 3. Halte Dich an die hier definierte Roadmap-Phase, an der Du arbeitest, und aktualisiere die Checkboxen
>    (`[ ]`, `[-]`, `[x]`) in dieser Datei entsprechend Deinem Fortschritt.
> 4. Nach jeder Phase (oder Teilphase, in der Du Änderungen vornimmst) führe die in diesem Dokument
>    definierte Checkliste mit Taskfile-Kommandos aus (`task test:*`, `task quality:*`) und behebe ALLE Fehler.
> 5. Nimm keine Commits/Pushes vor – diese bleiben Matze vorbehalten.
> 6. Respektiere die Coding-Guidelines (ECS, Rector, PHPStan-Level) und den bestehenden Stil des Projekts.
> 7. Kommuniziere Veränderungen und größere Designentscheidungen klar (z. B. in Pull-Request-Beschreibungen
>    oder separaten Dokumentationsabschnitten), damit andere Entwickler sie nachvollziehen können.

