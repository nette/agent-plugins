# Installing Nette

Do you want to harness the benefits of Nette in your existing project, or are you planning to create a new project based on Nette? This guide will walk you through the installation step by step.

## How to Add Nette to Your Project

Nette offers a collection of useful and sophisticated packages (libraries) for PHP. To incorporate them into your project, follow these steps:

1. **Set up [Composer](https://doc.nette.org/en/best-practices/composer):** This tool is essential for the easy installation, update, and management of libraries required for your project.
2. **Choose a [package](https://nette.org/en/packages):** Let's say you need to navigate the file system, which [Finder](../utils/finder.md) from the `nette/utils` package does excellently. You can find the package name in the right column of its documentation.
3. **Install the package:** Run this command in the root directory of your project:

```shell
composer require nette/utils
```

Do you prefer a graphical interface? Check out the [guide](https://www.jetbrains.com/help/phpstorm/using-the-composer-dependency-manager.html) on installing packages in the PhpStorm environment.

## How to Start a New Project with Nette

If you want to create an entirely new project on the Nette platform, we recommend using the pre-configured [Web Project](https://github.com/nette/web-project) skeleton:

1. **Set up [Composer](https://doc.nette.org/en/best-practices/composer).**
2. **Open the command line** and navigate to the root directory of your web server, e.g., `/var/www/html`, `C:/xampp/htdocs`, `/Library/WebServer/Documents`.
3. **Create the project** using this command:

```shell
composer create-project nette/web-project PROJECT_NAME
```

4. **Not using Composer?** Just download the [Web Project in ZIP format](https://github.com/nette/web-project/archive/preloaded.zip) and extract it. But trust us, Composer is worth it!
5. **Set permissions:** On macOS or Linux systems, set [write permissions](troubleshooting.md#setting-directory-permissions) for directories.
6. **Open the project in a browser:** Enter the URL `http://localhost/PROJECT_NAME/www/`. You'll see the landing page of the skeleton:

![](https://files.nette.org/git/nette/4.x/qs-welcome.webp)

Congratulations! Your website is now ready for development. Feel free to remove the welcome template and start building your application.

One of the advantages of Nette is that the project works immediately without the need for configuration. However, if you encounter any issues, consider looking at [solutions to common problems](troubleshooting.md#nette-is-not-working-white-page-is-displayed).

If you're starting with Nette, we recommend continuing with the [Create Your First Application tutorial](https://doc.nette.org/en/quickstart).

## Tools and Recommendations

For efficient work with Nette, we recommend the following tools:

- [High-quality IDE with plugins for Nette](https://doc.nette.org/en/tools/ide)
- Version control system Git
- [Composer](https://doc.nette.org/en/best-practices/composer)
