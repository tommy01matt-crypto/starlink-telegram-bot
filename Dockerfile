FROM php:8.0-cli

# Install extensions
RUN docker-php-ext-install curl json

# Set working directory
WORKDIR /var/www

# Copy files
COPY . .

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 8080

# Start command
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
