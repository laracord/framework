#!/bin/sh

set -eux

# Add a welcome message
echo '👋 Welcome to Laracord Development 🤖' | tee /usr/local/etc/vscode-dev-containers/first-run-notice.txt

# This is the default .env file used by the application
FALLBACK_APP_ENV_FILE="/app/framework/.devcontainer/.env.example";
APP_ENV_FILE="${FALLBACK_APP_ENV_FILE}";

# If .env file exists in the workspace, let's use that instead
if [ ! -z ${APP_ENV+x} ] && [ -f "/app/framework/.devcontainer/.env.${APP_ENV}" ]; then
  APP_ENV_FILE="/app/framework/.devcontainer/.env.${APP_ENV}";
elif [ -f "/app/framework/.devcontainer/.env" ]; then
  APP_ENV_FILE="/app/framework/.devcontainer/.env";
fi

. "${APP_ENV_FILE}";
# source our application env vars

REPOSITORY_URL="${REPOSITORY_URL:-"https://github.com/laracord/laracord.git"}"

mkdir -p /app/laracord
chown -R vscode:vscode /app/laracord
cd /app/laracord

# Install composer
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# if composer.json already exists, exit early
if [ -f "composer.json" ]; then
  exit 0
fi

# Clone the repository
git clone --depth=1 ${REPOSITORY_URL} . \
  && rm -rf .git

# if composer.json does not exist, exit with error message
if [ ! -f "composer.json" ]; then
  echo "composer.json not found in /app/laracord"
  exit 1
fi

# copy .env file if APP_ENV_FILE is default, otherwise link .env file
[ "${APP_ENV_FILE}" = "${FALLBACK_APP_ENV_FILE}" ] \
  && cp "${APP_ENV_FILE}" /app/laracord/.env \
  || ln -fs "${APP_ENV_FILE}" /app/laracord/.env

cd /app/laracord

# Install Composer dependencies
composer -d /app/laracord install --no-progress --optimize-autoloader --prefer-dist --no-interaction

# Link the workspace folder
cat /app/laracord/composer.json | jq ".repositories += [{ type: \"path\", url: \"/app/framework\" }]" > /app/laracord/composer.tmp \
  && rm /app/laracord/composer.json \
  && mv /app/laracord/composer.tmp /app/laracord/composer.json \
  && composer require -d /app/laracord $(cat "/app/framework/composer.json" | jq '.name' | tr -d '"') --no-interaction -W

# Set filesystem permissions
chown -R vscode:vscode /app/laracord
find /app/laracord/ -type d -exec chmod g+s {} \;
chmod g+w -R /app/laracord
