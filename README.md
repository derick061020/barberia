# Barbería Wallet — MVP

Plataforma tipo "tarjeta de fidelidad digital" para barberías/negocios. Genera pases
para **Apple Wallet** y **Google Wallet**, los gestiona desde un panel (CRM) y permite
**actualizarlos remotamente** disparando una notificación push automática al teléfono
del cliente (igual que el ejemplo del video).

## Qué hace ya (MVP)

- **Registro de clientes** (nombre, teléfono, email, nivel, puntos, estado) → CRM.
- **Generación del pase** por cliente (serial único + token de autenticación).
- **Apple Wallet**: genera el `.pkpass` firmado (pass.json + manifest + firma PKCS#7).
- **Google Wallet**: crea la clase/objeto y el enlace "Add to Google Wallet" (JWT).
- **Página pública del pase** (se abre en el teléfono con ambos botones y QR).
- **Actualización remota + push**:
  - Apple: web service oficial de PassKit + push APNs (`PushApplePassUpdate`).
  - Google: `PATCH` del objeto → Google notifica al teléfono automáticamente.
- **Segmentación** por nivel: `nuevo`, `frecuente`, `vip`, `premium`.
- **Notificación por proximidad**: si configuras la ubicación del local (panel →
  📍 Negocio), el pase aparece en la pantalla de bloqueo cuando el cliente está cerca.
  Lo gestionan iOS/Android de forma nativa (geocerca), sin enviar push manual:
  Apple usa `locations` + `maxDistance`, Google usa `locations` (radio fijo ~150 m).

## Stack

Laravel 13 · PHP 8.5 · SQLite (por defecto) · Tailwind (CDN) · `firebase/php-jwt` · `google/auth`

## Arranque

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

- Panel: <http://127.0.0.1:8000/panel/clientes>
- Página del pase: se obtiene desde la ficha de cada cliente (QR + enlace).

Sin credenciales configuradas, el panel y los pases funcionan, pero los botones de
wallet muestran "pendiente de configurar". Para activarlos:

## Configurar Apple Wallet (PassKit)

Requiere una cuenta **Apple Developer** ($99/año).

1. En el portal de Apple, crea un **Pass Type ID** (ej. `pass.com.tunegocio.barberia`).
2. Genera un **certificado** para ese Pass Type ID y expórtalo como `.p12`.
3. Descarga el certificado intermedio **WWDR** de Apple (`AppleWWDRCAG4.cer`).
4. Conviértelos a PEM y colócalos en `storage/app/passkit/certs/`:

   ```bash
   openssl pkcs12 -in Certificates.p12 -clcerts -nokeys -out cert.pem
   openssl pkcs12 -in Certificates.p12 -nocerts -out key.pem        # pide passphrase
   openssl x509 -inform der -in AppleWWDRCAG4.cer -out wwdr.pem
   ```

5. En `.env`:

   ```
   APPLE_PASS_TYPE_ID=pass.com.tunegocio.barberia
   APPLE_TEAM_ID=TU_TEAM_ID
   APPLE_CERT_PASSWORD=la_passphrase_de_key_pem
   ```

> El `webServiceURL` del pase es `APP_URL/wallet`. Para que Apple llame al web service
> y para las notificaciones push, `APP_URL` debe ser **HTTPS público** (en local usa
> un túnel tipo ngrok/cloudflared).

## Configurar Google Wallet

1. Proyecto en **Google Cloud** con la **Google Wallet API** activada.
2. Crea una **cuenta de servicio**, descarga su JSON y guárdalo en
   `storage/app/passkit/google-service-account.json`.
3. Obtén tu **Issuer ID** en el Google Pay & Wallet Console y autoriza la cuenta de servicio.
4. En `.env`:

   ```
   GOOGLE_WALLET_ISSUER_ID=tu_issuer_id
   GOOGLE_WALLET_CLASS_SUFFIX=barberia_loyalty
   GOOGLE_WALLET_ORIGINS=https://tudominio.com
   ```

## Probar el firmado de Apple sin cuenta de pago (opcional)

Para validar el pipeline de `.pkpass` localmente, puedes usar certificados autofirmados
(el pase NO será válido en un iPhone real, pero verifica que la firma se genera bien):

```bash
CERTS=storage/app/passkit/certs
openssl req -x509 -newkey rsa:2048 -keyout $CERTS/key.pem -out $CERTS/cert.pem -days 365 -nodes -subj "/CN=Pass Test"
openssl req -x509 -newkey rsa:2048 -keyout /tmp/w.pem -out $CERTS/wwdr.pem -days 365 -nodes -subj "/CN=WWDR Test"
# define APPLE_PASS_TYPE_ID y APPLE_TEAM_ID en .env (cualquier valor), luego:
php artisan tinker --execute='file_put_contents("/tmp/t.pkpass", App\Services\ApplePassBuilder::make()->build(App\Models\Pass::first()));'
unzip -l /tmp/t.pkpass
```

## Estructura relevante

| Archivo | Rol |
|---|---|
| `app/Services/ApplePassBuilder.php` | Genera y firma el `.pkpass` |
| `app/Services/GoogleWalletService.php` | Clase/objeto + JWT de Google Wallet |
| `app/Http/Controllers/WalletWebServiceController.php` | Web service oficial de PassKit |
| `app/Jobs/PushApplePassUpdate.php` | Push APNs a los iPhone registrados |
| `app/Http/Controllers/Panel/ClientController.php` | CRM (alta/edición + disparo de push) |
| `app/Http/Controllers/Panel/BusinessController.php` | Configuración del negocio + ubicación (proximidad) |
| `app/Http/Controllers/PassController.php` | Página pública + descarga del pase |
| `config/passkit.php` | Toda la configuración de credenciales |

## Procesar la cola (push real)

Los push de Apple se encolan. En producción ejecuta un worker:

```bash
php artisan queue:work
```

## Siguientes pasos sugeridos (fuera del MVP)

- Autenticación/multi-tenant (varios negocios y usuarios).
- Módulo de **campañas** (enviar promo a un segmento → push masivo).
- Subida de **logo/colores** por negocio desde el panel.
- Estadísticas (altas, instalaciones, canjes).
# barberia
