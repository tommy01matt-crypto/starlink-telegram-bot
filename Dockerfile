FROM php:8.0-apache

# Install required PHP extensions
RUN docker-php-ext-install curl json pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY --chown=www-data:www-data . .

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install dependencies (run as www-data)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose port 80 for Apache
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
