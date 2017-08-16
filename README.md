
# This is fork of SUPLA-CLOUD

Supla-Cloud require Apache 2.x, PHP 7.x and MySQL.

Installation on Debian 9
========================

    curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
    sudo apt-get install -y nodejs

    git clone https://github.com/IoTAqua/supla-cloud.git
    cd supla-cloud


New/Drop DB:
    mysql -p -u root
    * DROP DATABASE supla;
    * CREATE DATABASE supla;
    * CREATE USER 'supla'@'localhost' IDENTIFIED BY '<mysql-supla-password>';
    * GRANT ALL PRIVILEGES ON supla.* To 'supla'@'localhost';
    * FLUSH PRIVILEGES;


    vi app/config/parameters.yml
    * set "database_password:" <mysql-supla-password>
    * set "mailer_from:" <admin@domain>
    * set "supla_server:" <supla server address>
    * set "recaptcha_site_key:" Site key from www.google.com/recaptcha
    * set "recaptcha_secret:" Secret key from www.google.com/recaptcha
    * set "secret:" Generated token

    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --no-dev --optimize-autoloader
    php composer.phar run-script webpack


New/Drop DB:
    php bin/console doctrine:database:drop --force
    php bin/console doctrine:database:create --env=prod
    php bin/console doctrine:schema:create --env=prod

Remove all except latest in 'app/DoctrineMigrations' and chage file to dummy update db.

    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

Update DB:
    php bin/console doctrine:schema:update --force


    php bin/console cache:clear --env=prod --no-warmup

    sudo cp -R ../supla-cloud /var/www/
    sudo chown -R root:www-data /var/www/supla-cloud
    sudo chown -R www-data:www-data /var/www/supla-cloud/var
    sudo chmod 640 /var/www/supla-cloud/app/config/*

Setup Apache
============

Config must have:

    DocumentRoot /var/www/supla-cloud/web
    Directory /var/www/supla-cloud/web>
      AllowOverride All
      Order Allow,Deny
      Allow from All
    </Directory>
