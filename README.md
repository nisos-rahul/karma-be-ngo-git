Setup -

The present configuration configures Karma backend NGO to run on Apache port 8082.

Get the code from bitbucket in /home/devuser/projects/karma-be-ngo folder.

The following packages need to be installed -

    sudo apt-get install nginx debconf-utils  apache2 apache2-utils php5 libapache2-mod-php5 php5-curl php5-gd php5-mcrypt mysql-server php5-mysql

Run the command -

    sudo a2enmod rewrite
    sudo a2enmod headers

Database setup (considering mysql username and password is root and root respectively)-

    echo "CREATE USER 'admin'@'localhost' IDENTIFIED BY '\!karma\@'" | mysql -uroot -proot
    echo "CREATE DATABASE karma" |  mysql -uroot -proot
    echo "GRANT ALL ON karma.* TO 'admin'@'localhost'" |  mysql -uroot -proot
    echo "flush privileges" |  mysql -uroot -proot

DDL for the database - karma project in bitbucket - sqldumps/dailydumps/ddl.sql

Edit /etc/apache2/ports.conf -

    sudo vi /etc/apache2/ports.conf

Change Listen 80 to -
    
    Listen 8082

Edit /etc/apache2/apache2.conf 
    
    sudo vi /etc/apache2/apache2.conf 

Change -

    <Directory /var/www/>
     Header add Access-Control-Allow-Origin "*"
     Header add Access-Control-Allow-Headers "origin, x-requested-with, content-type, X-Auth-Token"
     Header add Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"
     Options Indexes FollowSymLinks
     AllowOverride All
     Require all granted
    </Directory>

Add -

    <Directory /home/devuser/projects/karma-be-ngo/>
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
    </Directory>

In /etc/apache2/sites-available
create a file - karma-be-ngo.conf

    sudo touch /etc/apache2/sites-available/karma-be-ngo.conf

Edit the file -

    sudo vi /etc/apache2/sites-available/karma-be-ngo.conf

Copy the following contents to the file -

    <VirtualHost *:8082>
        ServerAdmin webmaster@localhost
        DocumentRoot /home/devuser/projects/karma-be-ngo/
        #Define url rewriting rules to remove index.php from url
        <Directory /home/devuser/projects/karma-be-ngo/>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Headers "X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, X-Auth-Token, x-access-token, x-auth-token, apporigin"
        Header set Access-Control-Allow-Methods "GET, POST, OPTIONS, PUT, DELETE"
        Header set Cache-Control "no-cache, must-revalidate"
        </Directory>


        ErrorLog /var/log/apache2/karma-be-ngo-error.log
        CustomLog /var/log/apache2/karma-be-ngo-access.log combined
    </VirtualHost>

run the following commands-

    cd /etc/apache2/sites-available
    sudo a2ensite karma-be-ngo.conf
    sudo service apache2 reload

if already created nginx_vhost configuration file for Karma project, edit nginx_vhost
    
    sudo vi /etc/nginx/sites-available/nginx_vhost

In /etc/nginx/sites-available/nginx_vhost file, add the following -

    location /karma-be-ngo/{
        proxy_pass http://localhost:8082/;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Authorization $http_authorization;
        proxy_set_header  Host $http_host;
        proxy_redirect    off;
        #try_files $uri $uri/ @other;
        access_log /var/log/nginx/karma-be-ngo.access.log;
        error_log  /var/log/nginx/karma-be-ngo.error.log;
    }

Restart nginx -

    sudo service nginx restart

If nginx_vhost is not already created, please follow all the steps till running commands to setup Nginx using the configuration file nginx_vhost  

Create file nginx_vhost using -

    sudo touch /etc/nginx/sites-available/nginx_vhost

Copy the following contents into the file -

    server {
    listen 80 default_server;
    listen [::]:80 default_server ipv6only=on;
    server_name localhost;
    #root /home/devuser/projects; 
    keepalive_timeout 5;
    charset utf-8;
    index index.html index.htm;

     location /karma-be-ngo/{
        proxy_pass http://localhost:8082/;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Authorization $http_authorization;
        proxy_set_header  Host $http_host;
        proxy_redirect    off;
        #try_files $uri $uri/ @other;
        access_log /var/log/nginx/karma-be-ngo.access.log;
        error_log  /var/log/nginx/karma-be-ngo.error.log;
    }

    # Serve static resources
    location ^~ /(scripts.*js|styles|images|image) {
   	gzip_static on;
   	expires 1y;
   	add_header Cache-Control public;
   	add_header ETag "";

   	break;
    }

    }

Run the following commands to setup Nginx using the configuration file nginx_vhost created as above

    sudo ln -s /etc/nginx/sites-available/nginx_vhost /etc/nginx/sites-enabled/ | true
    sudo rm -rf /etc/nginx/sites-available/default
    sudo service nginx restart