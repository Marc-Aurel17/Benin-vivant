# Render ne supporte pas PHP nativement (seulement Node.js/Bun, Python, Ruby,
# Go, Rust, Elixir) : il faut donc passer par une image Docker. Ce Dockerfile
# reproduit un environnement équivalent à XAMPP (Apache + PHP + PDO MySQL).
FROM php:8.2-apache

# Extensions PHP utilisées par le projet : pdo_mysql (connexion BDD via PDO),
# gd (traitement d'images éventuel), et mod_headers/mod_rewrite côté Apache
# (utilisés par backend-php/.htaccess et uploads/.htaccess).
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod headers rewrite

# Autorise les .htaccess du projet (AllowOverride All) : nécessaire pour que
# backend-php/.htaccess et uploads/.htaccess soient appliqués.
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Contenu du dossier backend-php/ copié tel quel comme racine du site Apache.
COPY . /var/www/html/

# Le dossier uploads/ doit être inscriptible par Apache (www-data) pour les
# téléversements (médiathèque, pièces d'identité, etc.).
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
