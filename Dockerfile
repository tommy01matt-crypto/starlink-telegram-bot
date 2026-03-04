FROM php:8.0-apache

# Install system dependencies required for PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
# curl, json, pdo ya vienen incluidos en PHP 8.0
RUN docker-php-ext-install pdo_mysql zip

# Enable Apache mod_rewrite (required for .htaccess)
RUN a2enmod rewrite

# Copy project files to /var/www/html
COPY --chown=www-data:www-data . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install dependencies (run as www-data)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose port 80 for Apache
EXPOSE 80

# Start Apache with custom DocumentRoot pointing to public folder
# Also configure webhook automatically on startup
CMD sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf && \
    php /var/www/html/setWebhook.php > /tmp/webhook_setup.log 2>&1 && \
    apache2-foreground
