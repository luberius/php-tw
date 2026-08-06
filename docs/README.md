# PHP-TW Documentation

PHP-TW is a Composer-based project template for creating PHP web applications with integrated Tailwind CSS v4. It provides a zero-configuration development environment with live CSS compilation and modern CSS-based theming.

## Documentation Index

1. [Getting Started](getting-started.md) - Installation and first steps
2. [Architecture](architecture.md) - Project structure and design patterns
3. [CLI Commands](cli-commands.md) - Available commands and usage
4. [Development Guide](development.md) - How to develop and extend
5. [Post-Install Process](post-install-process.md) - How project scaffolding works

## Quick Start

```bash
# Create a new project
composer create-project luberius/php-tw my-project

# Start development server
cd my-project
php wand serve
```

Visit `http://127.0.0.1:6969` to see your application.

## Key Features

- **Single Command Setup** - Create projects instantly with Composer
- **Live Development** - Development server with live CSS compilation
- **Tailwind CSS v4** - Latest version with CSS-based configuration
- **Automatic Binary Management** - No Node.js required
- **Extensible CLI** - Built on Symfony Console with auto-discovery
- **Zero Config** - Works out of the box with sensible defaults

## Requirements

- PHP 8.1 or higher
- Composer
- Internet connection (for initial Tailwind CLI download)

## Support

- Package: `luberius/php-tw`
- Git: See repository for issues and contributions
