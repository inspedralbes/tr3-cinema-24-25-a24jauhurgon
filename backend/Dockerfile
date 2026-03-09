FROM php:8.2-cli-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --fix-missing --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    mariadb-client

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy existing application directory contents
COPY . /app

# Install dependencies (ignoring errors if vendor exists with weird perms)
RUN composer install --no-interaction --optimize-autoloader || true

# Expose port
EXPOSE 8000

# Start application and ensure dependencies are installed
CMD ["sh", "-c", "composer install --no-interaction --optimize-autoloader && php artisan serve --host=0.0.0.0 --port=8000"]
