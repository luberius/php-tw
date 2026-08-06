# PHP-TW: PHP Project Template with Tailwind CSS

PHP-TW is a project template that integrates PHP with Tailwind CSS, providing a streamlined setup for web development projects. It offers a pre-configured environment that you can quickly set up using Composer.

## Features

- Quick project setup with a single Composer command
- Integrated Tailwind CSS for efficient styling
- Built-in development server with live CSS updates
- Pre-configured project structure for immediate development

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

You can create a new PHP-TW project using Composer's `create-project` command:

```bash
composer create-project luberius/php-tw my-new-project
```

This will create a new directory `my-new-project` with all the necessary files and dependencies installed.

## Usage

### Starting the Development Server

After creating your project, navigate to the project directory and start the development server:

```bash
cd my-new-project
php wand serve
```

This starts the PHP development server and Tailwind CSS watcher. The first available port from 6969 through 7000 is selected and displayed in the terminal.

### Building for Production

```bash
php wand build
```

This compiles minified CSS, optimizes Composer's autoloader, generates an OPcache preload file, and creates an Apache production configuration. The command returns a failure status if any required build step fails.

## Project Structure

```
my-new-project/
├── app/
│   ├── css/
│   │   └── app.css
│   └── index.php
├── bootstrap/
│   ├── app.php
│   └── commands/
│       ├── BuildCommand.php
│       └── ServeCommand.php
├── vendor/
├── .gitignore
├── composer.json
└── wand
```

- `app/`: Contains your application code
- `app/css/app.css`: Main CSS file (with Tailwind directives)
- `app/index.php`: Entry point of your application
- `bootstrap/`: Contains files for bootstrapping your application
- `vendor/`: Composer dependencies
- `bootstrap/commands/`: Argument-safe development and production commands
- `wand`: Command-line script for various project tasks

## Customization

### Tailwind CSS

PHP-TW uses Tailwind CSS v4's CSS-first configuration. Edit `app/css/app.css` and add `@theme`, `@utility`, or other Tailwind directives directly in CSS. Refer to the [Tailwind CSS theme documentation](https://tailwindcss.com/docs/theme) for details.

### Adding New Commands

To add new commands to your project, create a new PHP file in the `bootstrap/commands/` directory. The command will be automatically registered and available through the `wand` script.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the MIT license.

## Support

If you encounter any problems or have any questions, please open an issue on the [GitHub repository](https://github.com/luberius/php-tw).

## Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/)
- [Symfony Console Component](https://symfony.com/doc/current/components/console.html)

---

Happy coding with PHP-TW!
