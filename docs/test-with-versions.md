# Test Scripts

## Testing with Specific Versions

Das `test-with-versions.sh` Script ermöglicht es, das Package mit spezifischen Framework-Versionen zu testen.

### Verwendung

```bash
# Test mit Laravel 9 (Dependencies werden automatisch aufgelöst)
./scripts/test-with-versions.sh --laravel 9

# Test mit Laravel 11 und PHPStan
./scripts/test-with-versions.sh --laravel 11 --phpstan

# Test mit Symfony 6
./scripts/test-with-versions.sh --symfony 6

# Test mit Doctrine ORM 3
./scripts/test-with-versions.sh --doctrine 3

# Nur Dependencies aktualisieren, keine Tests
./scripts/test-with-versions.sh --laravel 9 --no-tests
```

### Optionen

- `-l, --laravel VERSION` - Test mit Laravel-Version (9, 10 oder 11)
- `-s, --symfony VERSION` - Test mit Symfony-Version (6 oder 7)
- `-d, --doctrine VERSION` - Test mit Doctrine ORM-Version (2 oder 3)
- `-p, --phpstan` - PHPStan nach den Tests ausführen
- `--no-tests` - Tests überspringen
- `-h, --help` - Hilfe anzeigen

### Automatische Dependency-Auflösung

Das Script verwendet `composer update -W` (--with-all-dependencies), um automatisch kompatible Versionen aller Dependencies aufzulösen:

- **Laravel 9**: Installiert automatisch Symfony 6, Pest 2.x und andere kompatible Packages
- **Laravel 10**: Installiert automatisch Symfony 6/7 (je nach Verfügbarkeit) und kompatible Packages
- **Laravel 11**: Installiert automatisch Symfony 7, Pest 3.x und andere kompatible Packages
- **Symfony 6**: Installiert automatisch Pest 2.x und kompatible Packages
- **Symfony 7**: Installiert automatisch Pest 3.x und kompatible Packages

Du musst **keine** Symfony- oder Pest-Versionen manuell angeben - Composer löst diese automatisch auf!

### Funktionsweise

Das Script:
1. Erstellt ein Backup der `composer.json`
2. Fügt die gewünschte Framework-Version zu den dev-dependencies hinzu
3. Führt `composer update -W` aus, um alle Dependencies automatisch aufzulösen
4. Zeigt die installierten Versionen an
5. Führt die Tests aus
6. Optional: Führt PHPStan aus
7. Stellt die Original-`composer.json` wieder her

### Composer Scripts

Für häufig verwendete Kombinationen gibt es Task-Shortcuts:

```bash
# Laravel
task test:laravel9
task test:laravel10
task test:laravel11
task test:matrix:laravel  # Alle Laravel-Versionen

# Symfony
task test:symfony6
task test:symfony7

# Doctrine
task test:doctrine2
task test:doctrine3
```

