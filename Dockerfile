FROM php:8.2-apache

# Устанавливаем расширения PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Копируем файлы в контейнер
COPY . /var/www/html/

# Настраиваем Apache
RUN a2enmod rewrite

# Создаем директорию для логов
RUN mkdir -p /var/log/php

# Скрипт запуска
RUN echo '#!/bin/bash\n\
# Инициализируем базу данных\n\
echo "Запуск инициализации БД..."\n\
php /var/www/html/Backend/init_pgsql.php > /var/log/php/db_init.log 2>&1\n\
\n\
# Настраиваем порт для Railway\n\
sed -i "s/80/${PORT:-80}/" /etc/apache2/sites-available/000-default.conf\n\
sed -i "s/80/${PORT:-80}/" /etc/apache2/ports.conf\n\
\n\
# Запускаем Apache\n\
apache2-foreground\n\
' > /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"] 