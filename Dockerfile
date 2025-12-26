# Gunakan image PHP CLI terbaru
FROM php:8.1-cli

# Set working directory di container
WORKDIR /app

# Salin semua file dari repo ke container
COPY . /app

# Install ekstensi atau tools tambahan (kalau perlu)
RUN apt-get update && apt-get install -y curl unzip git

# Expose port yang nanti digunakan Render (bebas, misal 10000)
EXPOSE 10000

# Jalankan PHP built-in server untuk index.php
CMD ["php", "-S", "0.0.0.0:10000", "index.php"]
