FROM php:8.2-cli

# Install system dependencies (including SSL certs for SMTP)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    libssl-dev \
    ca-certificates \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (including openssl for email)
RUN docker-php-ext-install pdo_mysql curl mbstring

# Enable openssl (already compiled in php:8.2-cli, just ensure it's active)
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Create uploads directory
RUN mkdir -p /app/uploads

# Expose port
EXPOSE 8080

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app", "index.php"]
