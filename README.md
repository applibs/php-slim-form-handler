# PHP Slim Form Handler

Simple back-end form handler / emailer with CORS support, built with Slim API framework + PHPMailer.

Uses JSON for easy compatibility with JavaScript projects.

Useful as a basic back-end for front-end apps that have limited requirements such as a contact form. For example, a static site or landing page created in React.

PHPMailer enables SMTP for reliable mail delivery. The code is intended to be easily customized and deployed on limited LAMP-stack environments including shared web hosts and lightweight VPS servers.

## Usage

* Copy `src/config.json.example` to `src/config.json` and update with your SMTP settings
* Run `composer install --no-dev` in the project root to install project dependencies (Slim, PHPMailer)
* Deploy such that only the `src/public/` folder is served by the web server and parent folders are not

### Security

The `src/public/.htaccess` file is important for the API to work as expected.

Note that additional `.htaccess` files have been added to the `src/` folders to protect files such as `config.json` in the event that an accident or misconfiguration serves more than the `public/` folder to the Internet.

### Extending + Customizing

If you need to customize the PHPMailer to support a particular email host or configuration, refer to the project docs + tutorials: https://github.com/PHPMailer/PHPMailer/wiki/Tutorial

The API can be expanded upon using Slim. More complex projects should split `App.php` functionality into multiple files that define routes, controllers, etc. https://www.slimframework.com/docs/

### PHP Version

Slim3 works best with later versions of PHP. The code has been run on machines with both PHP 7.1 + 7.2.

If you're using a shared host, note that most shared hosts let you choose your PHP version in their control panel.

## Notes

### Composer

Composer is used to manage package dependencies.

A common approach is to use Composer to install dependencies on a local machine, and then upload the deployable project to the web server.

To get Composer: https://getcomposer.org/download/

* Linux tutorial: https://www.digitalocean.com/community/tutorials/how-to-install-and-use-composer-on-ubuntu-18-04
* MacOS install: can be installed with `brew` with the command: `brew install composer`
* Windows install: use script installation or download the binary installer (https://getcomposer.org/Composer-Setup.exe)

### Email Deliverability

To help minimize the chance of deliverability issues:

* Send from an email address / SMTP account that you own and control
* Ensure that your DNS server has SPF records in place that declare that identify your SMTP server as an authorized sender of emails on behalf of your domain.

### htaccess Files

The `.htaccess` files are written in Apache 2.4+ syntax and require `mod_rewrite` to be enabled.

Almost every remotely modern shared host and VPS/server deployment that supports a LAMP stack, WordPress, etc will meet these requirements.
