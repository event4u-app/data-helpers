# Docker Setup

This directory contains the Docker setup for local testing with multiple PHP versions.

## Quick Start

```bash
# Start containers
docker-compose up -d

# Run tests
./docker/test.sh -p 8.4 -l 11

# Run all tests from matrix
./docker/test-all.sh
```

## Documentation

The full documentation is available in the `docs/` directory:

- **[docs/docker-setup.md](../docs/docker-setup.md)** - Complete Docker setup documentation
- **[docs/taskfile-guide.md](../docs/taskfile-guide.md)** - Task runner guide

## Files in this directory

- `Dockerfile` - Multi-version PHP Docker image
- `test.sh` - Script to run tests with specific PHP version and framework
- `test-all.sh` - Script to run complete test matrix
- `README.md` - This file (pointer to documentation)

## Quick Commands

```bash
# Using scripts
./docker/test.sh -p 8.2 -l 9
./docker/test-all.sh

# Using Task (recommended)
task test PHP=8.2
task test:laravel9
task test:matrix

# Using Make
make test PHP=8.2 FW=l V=9
make test-all
```

See the documentation links above for detailed usage instructions.
