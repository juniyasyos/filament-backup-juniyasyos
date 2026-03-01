# Contributing

We love your input! We want to make contributing to Filament Backup as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

## Development Process

We use GitHub to host code, to track issues and feature requests, as well as accept pull requests.

## Pull Requests

Pull requests are the best way to propose changes to the codebase. We actively welcome your pull requests:

1. Fork the repo and create your branch from `main`.
2. If you've added code that should be tested, add tests.
3. If you've changed APIs, update the documentation.
4. Ensure the test suite passes.
5. Make sure your code lints.
6. Issue that pull request!

## Setting Up Development Environment

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM (for asset compilation)
- MySQL/PostgreSQL (for testing)

### Installation

1. Fork and clone the repository:
```bash
git clone https://github.com/yourusername/filament-backup.git
cd filament-backup
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create a test Laravel application or use the existing testbench setup:
```bash
composer test:setup
```

4. Run tests to ensure everything is working:
```bash
composer test
```

### Development Workflow

1. Create a new branch for your feature:
```bash
git checkout -b feature/amazing-new-feature
```

2. Make your changes following our coding standards
3. Write tests for new functionality
4. Run the test suite:
```bash
composer test
```

5. Run code quality checks:
```bash
composer lint
composer analyze
```

6. Commit your changes:
```bash
git commit -m "Add amazing new feature"
```

7. Push to your fork and submit a pull request

## Code Style

We follow PSR-12 coding standards and use several tools to maintain code quality:

### PHP Code Style
- **PSR-12** - Standard PHP coding style
- **Laravel Pint** - Automatic code formatting
- **PHPStan** - Static analysis for type safety
- **Pest** - Testing framework

Run code style fixes:
```bash
composer lint
```

Run static analysis:
```bash
composer analyze
```

### Naming Conventions

- **Classes**: PascalCase (`BackupJob`, `BackupService`)
- **Methods**: camelCase (`createBackup`, `validateStorage`)
- **Variables**: camelCase (`$backupJob`, `$storageConfig`)
- **Constants**: UPPER_SNAKE_CASE (`STATUS_COMPLETED`)
- **Database**: snake_case (`backup_jobs`, `created_at`)

## Testing

We strive for high test coverage. When adding new features:

1. Write unit tests for individual components
2. Write integration tests for complex workflows
3. Write feature tests for user-facing functionality

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
composer test tests/Unit/BackupJobTest.php

# Run with coverage
composer test:coverage
```

### Test Structure

```
tests/
├── Unit/           # Unit tests for individual classes
├── Feature/        # Feature tests for complete workflows
├── Integration/    # Integration tests for external services
└── Fixtures/       # Test data and mock files
```

## Documentation

- Update README.md for new features
- Update CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/) format
- Add PHPDoc comments for all public methods
- Update configuration documentation

## Issue Reporting

We use GitHub issues to track public bugs. Report a bug by [opening a new issue](https://github.com/juniyasyos/filament-backup/issues/new).

**Great Bug Reports** tend to have:

- A quick summary and/or background
- Steps to reproduce
  - Be specific!
  - Give sample code if you can
- What you expected would happen
- What actually happens
- Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

### Bug Report Template

```markdown
## Bug Description
Brief description of the issue

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- PHP version: 
- Laravel version: 
- Filament version: 
- Package version: 
- OS: 

## Additional Context
Add any other context about the problem here.
```

## Feature Requests

We welcome feature requests! Please provide:

1. **Use case**: Why is this feature needed?
2. **Proposed solution**: How should it work?
3. **Alternatives considered**: What other approaches did you consider?
4. **Implementation details**: Any technical considerations

## Security Vulnerabilities

**Do NOT** report security vulnerabilities through public GitHub issues. Instead, send an email to [security@yoursite.com](mailto:security@yoursite.com).

Include:
- Type of issue (e.g. buffer overflow, SQL injection, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

### Our Standards

- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

## Recognition

Contributors who make significant improvements will be:
- Added to the contributors list in README.md
- Credited in release notes
- Given commit access (for consistent contributors)

## Getting Help

- Check existing [documentation](README.md)
- Search [existing issues](https://github.com/juniyasyos/filament-backup/issues)
- Join our [Discord community](https://discord.gg/filament) (#backup-plugin channel)
- Ask questions in [GitHub Discussions](https://github.com/juniyasyos/filament-backup/discussions)

Thank you for contributing! 🎉