# Despliegue en un VPS Ubuntu

Guía para desplegar **Barbería Wallet** en un servidor Ubuntu limpio (22.04/24.04)
con Nginx + PHP-FPM + HTTPS (Let's Encrypt) + worker de cola.

> **Requisito crítico:** Apple y Google necesitan que `APP_URL` sea **HTTPS público**.
> Sin dominio con TLS, los pases se generan pero NO hay actualización remota ni push.

Asume:
- Un dominio (o subdominio) apuntando por DNS `A` a la IP del VPS. Ej: `wallet.tudominio.com`.
- Acceso `sudo` por SSH.

---

## 1. Paquetes del sistema

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 + extensiones que usa el proyecto
sudo apt install -y php8.3-fpm php8.3-cli \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-sqlite3 php8.3-bcmath php8.3-gd \
    nginx git unzip certbot python3-certbot-nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

> `php8.3-zip` (ZipArchive) y `openssl` son imprescindibles para firmar el `.pkpass`.
> No hace falta Node/npm: Tailwind va por CDN.

## 2. Traer el código

```bash
sudo mkdir -p /var/www/barberia-wallet
sudo chown -R $USER:$USER /var/www/barberia-wallet
git clone TU_REPO /var/www/barberia-wallet
cd /var/www/barberia-wallet

composer install --no-dev --optimize-autoloader
```

## 3. Configurar `.env`

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Valores de producción mínimos:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wallet.tudominio.com     # ¡HTTPS y tu dominio real!

# SQLite (simple) — o cambia a MySQL si prefieres (ver nota abajo)
DB_CONNECTION=sqlite

QUEUE_CONNECTION=database                 # el worker procesa los push de Apple

# Apple Wallet
APPLE_PASS_TYPE_ID=pass.com.tunegocio.barberia
APPLE_TEAM_ID=TU_TEAM_ID
APPLE_CERT_PASSWORD=la_passphrase_de_key_pem

# Google Wallet
GOOGLE_WALLET_ISSUER_ID=tu_issuer_id
GOOGLE_WALLET_CLASS_SUFFIX=barberia_loyalty
GOOGLE_WALLET_ORIGINS=https://wallet.tudominio.com
```

## 4. Base de datos + migraciones

```bash
# SQLite: crear el fichero
touch database/database.sqlite

php artisan migrate --force        # --force: no pregunta en producción
php artisan db:seed --force        # opcional: negocio + clientes demo
```

> **Nota SQLite:** válido para un MVP de una barbería. Si esperas concurrencia alta
> o varios negocios, cambia a MySQL (`sudo apt install mysql-server php8.3-mysql`,
> crea la BD y pon `DB_CONNECTION=mysql` + credenciales en `.env`).

## 5. Subir certificados y assets (NO están en git)

```bash
mkdir -p storage/app/passkit/certs storage/app/passkit/assets
```

Desde tu máquina local, sube los ficheros con `scp`:

```bash
# Apple: los 3 PEM (ver README para generarlos)
scp cert.pem key.pem wwdr.pem  usuario@IP:/var/www/barberia-wallet/storage/app/passkit/certs/
# Apple: icon.png es OBLIGATORIO (más logo.png si quieres)
scp icon.png icon@2x.png logo.png usuario@IP:/var/www/barberia-wallet/storage/app/passkit/assets/
# Google: el JSON de la cuenta de servicio
scp google-service-account.json usuario@IP:/var/www/barberia-wallet/storage/app/passkit/google-service-account.json
```

## 6. Permisos

```bash
sudo chown -R www-data:www-data /var/www/barberia-wallet
sudo find /var/www/barberia-wallet -type f -exec chmod 644 {} \;
sudo find /var/www/barberia-wallet -type d -exec chmod 755 {} \;
# Escribibles por el servidor web:
sudo chmod -R ug+rwx storage bootstrap/cache
sudo chmod 664 database/database.sqlite
```

## 7. Nginx + HTTPS

```bash
sudo cp deploy/nginx.conf.example /etc/nginx/sites-available/barberia
sudo nano /etc/nginx/sites-available/barberia     # pon TU_DOMINIO
sudo ln -s /etc/nginx/sites-available/barberia /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# TLS gratis con Let's Encrypt (añade el bloque 443 automáticamente)
sudo certbot --nginx -d wallet.tudominio.com
# certbot renueva solo; comprobar: sudo certbot renew --dry-run
```

## 8. Worker de la cola (push de Apple)

```bash
sudo cp deploy/barberia-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now barberia-queue
systemctl status barberia-queue          # debe estar "active (running)"
```

## 9. Cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. Verificar

```bash
# La app responde por HTTPS
curl -I https://wallet.tudominio.com/panel/clientes

# El pase público carga (usa un serial real de tu BD)
# Abre en el móvil: https://wallet.tudominio.com/p/{serial}
```

Checklist de que la parte "wallet" funciona:
- [ ] Panel accesible por HTTPS.
- [ ] Botón "Add to Apple Wallet" descarga un `.pkpass` que el iPhone instala.
- [ ] Botón "Add to Google Wallet" añade el pase.
- [ ] Al editar puntos/nivel en el panel, el push llega al teléfono
      (Google al instante; Apple vía el worker — mira `journalctl -u barberia-queue -f`).
- [ ] Con lat/long en 📍 Negocio, el pase salta al acercarte al local.

---

## Redesplegar (cada actualización de código)

```bash
cd /var/www/barberia-wallet
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart barberia-queue     # recarga el código en el worker
sudo systemctl reload php8.3-fpm
```

## Problemas típicos

| Síntoma | Causa probable |
|---|---|
| Apple no descarga el `.pkpass` | Falta `icon.png`, certs PEM mal convertidos o `APPLE_CERT_PASSWORD` incorrecta |
| El push de Apple no llega | Worker parado (`systemctl status barberia-queue`) o `APP_URL` no es HTTPS público |
| Google "Add to Wallet" falla | `GOOGLE_WALLET_ORIGINS` no coincide con tu dominio, o Issuer ID no autorizado |
| 500 al escribir | Permisos de `storage/`, `bootstrap/cache/` o `database.sqlite` |
| Apple no llama al web service | El dominio no es accesible públicamente por HTTPS (revisa DNS y certbot) |
