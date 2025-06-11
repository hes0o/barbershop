# Deployment Guide

This guide will help you deploy the Barbershop Management System to your production environment.

## Prerequisites

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- SSL certificate (recommended)
- Domain name

## Deployment Steps

### 1. Server Setup

1. Install required software:
   ```bash
   # For Ubuntu/Debian
   sudo apt update
   sudo apt install apache2 php mysql-server php-mysql php-mbstring php-xml php-curl
   ```

2. Enable required PHP extensions:
   ```bash
   sudo phpenmod mbstring
   sudo phpenmod xml
   sudo phpenmod curl
   ```

### 2. Database Setup

1. Create a new database:
   ```sql
   CREATE DATABASE your_database_name;
   CREATE USER 'your_database_user'@'localhost' IDENTIFIED BY 'your_database_password';
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_database_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. Import the database schema:
   ```bash
   mysql -u your_database_user -p your_database_name < sql/schema.sql
   ```

### 3. Application Deployment

1. Clone or upload the application to your web server:
   ```bash
   git clone [repository-url] /var/www/html/barbershop
   ```

2. Set proper permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/barbershop
   sudo chmod -R 755 /var/www/html/barbershop
   ```

3. Create and configure the environment file:
   ```bash
   cp config.template.php config.php
   # Edit config.php with your settings
   ```

### 4. Web Server Configuration

#### Apache Configuration

Create a new virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/barbershop
    
    <Directory /var/www/html/barbershop>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/barbershop-error.log
    CustomLog ${APACHE_LOG_DIR}/barbershop-access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/barbershop;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 5. SSL Configuration (Recommended)

1. Install Certbot:
   ```bash
   sudo apt install certbot python3-certbot-apache
   ```

2. Obtain SSL certificate:
   ```bash
   sudo certbot --apache -d your-domain.com
   ```

### 6. Security Measures

1. Enable HTTPS redirect
2. Set secure headers
3. Configure firewall:
   ```bash
   sudo ufw allow 'Apache Full'
   sudo ufw enable
   ```

### 7. Testing

1. Visit your domain to ensure the application is working
2. Test the registration process
3. Verify email functionality
4. Check appointment booking system
5. Test admin and barber dashboards

### 8. Monitoring

1. Set up error logging
2. Configure backup system
3. Monitor server resources
4. Set up uptime monitoring

## Maintenance

1. Regular backups:
   ```bash
   # Database backup
   mysqldump -u your_database_user -p your_database_name > backup.sql
   
   # Files backup
   tar -czf barbershop-backup.tar.gz /var/www/html/barbershop
   ```

2. Update PHP and dependencies regularly
3. Monitor error logs
4. Keep SSL certificate up to date

## Troubleshooting

Common issues and solutions:

1. 500 Internal Server Error
   - Check PHP error logs
   - Verify file permissions
   - Check database connection

2. Database Connection Issues
   - Verify database credentials
   - Check database server status
   - Ensure database user has proper permissions

3. File Upload Issues
   - Check PHP upload limits
   - Verify directory permissions
   - Check disk space

For additional support, please refer to the project's issue tracker or documentation. 