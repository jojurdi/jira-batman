# Batman Worklog Tracker

Reporte de horas registradas en Jira. Muestra tus worklogs agrupados por día con totales y comparación frente a la jornada configurada.

## Requisitos

- **PHP** >= 8.0 (con extensión `curl` y `json`)
- **Composer**

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio> jira-batman
cd jira-batman
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Variables de entorno

```bash
cp .env.example .env
```

| Variable                   | Descripción |
|---------------------------|-------------|
| `JIRA_BASE_URL`           | URL de tu Jira Cloud (ej: `https://miempresa.atlassian.net`). |
| `JIRA_OAUTH_CLIENT_ID`    | (Opcional) Client ID de la app OAuth 2.0. |
| `JIRA_OAUTH_CLIENT_SECRET`| (Opcional) Client Secret de la app OAuth 2.0. |
| `JIRA_OAUTH_REDIRECT_URI` | (Opcional) URL de callback registrada en Atlassian. |
| `TIMEZONE`                | Zona horaria (ej: `America/Mexico_City`). |
| `HOURS_PER_DAY`           | Horas por día para el cálculo de jornada (por defecto: 8). |

Hay dos formas de autenticarse:

- **OAuth 2.0 (3LO)** — recomendado. El usuario hace login con su cuenta de Atlassian; los tokens viven en la sesión PHP (cookies HttpOnly).
- **API token** — alternativa. La app pide email + token y los guarda en `localStorage` del navegador (y los envía al servidor vía cookies).

### 4a. Configurar OAuth (recomendado)

1. Entra en [developer.atlassian.com/console/myapps](https://developer.atlassian.com/console/myapps/) → **Create** → **OAuth 2.0 integration**.
2. Pon nombre (ej: "Jira Batman Worklog") y crea la app.
3. Menú lateral → **Permissions** → añade **Jira API** y configura los scopes:
   - `read:me`, `read:jira-user`, `read:jira-work`, `write:jira-work`, `offline_access`.
4. Menú lateral → **Authorization** → **Configure** junto a OAuth 2.0 (3LO):
   - **Callback URL**: la misma que pongas en `JIRA_OAUTH_REDIRECT_URI` (ej: `http://localhost:8080/oauth/callback.php`).
5. Menú lateral → **Settings** → copia **Client ID** y **Secret** y pégalos en `.env`.

### 4b. Configurar API token (alternativa)

1. Entra en [Atlassian Account Settings](https://id.atlassian.com/manage-profile/security/api-tokens).
2. Pulsa **Create API token**, pon un nombre y copia el token.
3. En la app, abre el modal **Configurar** y pega ahí tu email y el token.

### 5. Servidor web

El punto de entrada de la aplicación es la carpeta `public/`. Tienes dos opciones:

**Opción A – Servidor PHP incorporado (desarrollo):**

```bash
php -S localhost:8080 -t public
```

Luego abre en el navegador: **http://localhost:8080**

**Opción B – Nginx o Apache**

- **Nginx:** el `root` (o `alias`) debe apuntar a la carpeta `public` del proyecto. El resto de la aplicación (`.env`, `src/`, etc.) debe quedar fuera del document root por seguridad.
- **Apache:** crea un `VirtualHost` cuyo `DocumentRoot` sea la ruta a `public` y, si usas mod_rewrite, asegúrate de que `public/.htaccess` (si existe) redirija las peticiones a `index.php`.

Ejemplo mínimo Nginx (app en raíz del dominio):

```nginx
server {
    listen 80;
    server_name jira-batman.local;
    root /ruta/completa/jira-batman/public;
    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;  # o el socket que uses
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**App en subdirectorio (ej: `/jira-batman/`)**

Si despliegas en un subdirectorio y registraste el callback OAuth sin `.php`
(ej. `https://tu-dominio.com/jira-batman/oauth/callback`), añade una regla
de reescritura para mapear `/oauth/callback` → `/oauth/callback.php`:

```nginx
location /jira-batman/ {
    alias /ruta/completa/jira-batman/public/;
    try_files $uri $uri/ /jira-batman/index.php?$query_string;

    # Permitir callback OAuth sin extensión .php
    location = /jira-batman/oauth/callback {
        rewrite ^ /jira-batman/oauth/callback.php last;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

Y en `.env`:

```
APP_BASE_URL=/jira-batman/
JIRA_OAUTH_REDIRECT_URI=https://tu-dominio.com/jira-batman/oauth/callback
```

**Importante sobre los callbacks de Atlassian**

En [Developer Console](https://developer.atlassian.com/console/myapps/) → tu app → **Authorization** → **Callback URL**, registra **todas** las URLs que vayas a usar (Atlassian admite varias):

- Local: `http://localhost:8080/oauth/callback.php`
- Prod: `https://tu-dominio.com/jira-batman/oauth/callback`

El valor de `JIRA_OAUTH_REDIRECT_URI` en cada `.env` debe coincidir **exactamente** con la URL registrada para ese entorno.

## Uso

1. Abre en el navegador la URL donde está desplegada la app (ej: `http://localhost:8080` o tu dominio).
2. La primera vez pulsa **Configurar** e introduce la URL de Jira, tu email y el API token. Se guardan en **localStorage** (y se envían al servidor vía cookies).
3. El reporte muestra por defecto el **mes actual**. Puedes cambiar el rango con los filtros:
   - **Hoy** – solo el día actual.
   - **Semana** – lunes a viernes de la semana actual (hasta hoy).
   - **Mes** – desde el día 1 del mes hasta hoy.
   - **Personalizado** – elige fechas de inicio y fin.
4. En la parte superior verás el resumen (total de horas, diferencia frente a la jornada configurada) y, día a día, los worklogs con issue, resumen, proyecto y tiempo.
5. El botón de **configuración** (engranaje) permite cambiar o borrar credenciales (se guardan en localStorage).

## Estructura del proyecto

```
jira-batman/
├── public/
│   ├── index.php           # Entrada web
│   └── oauth/
│       ├── login.php       # Inicia el flujo OAuth
│       ├── callback.php    # Recibe el code y guarda tokens en sesión
│       └── logout.php      # Cierra la sesión OAuth
├── src/
│   ├── AuthSession.php     # Manejo de sesión PHP (tokens, CSRF, refresh)
│   ├── JiraClient.php      # Cliente API Jira (Bearer y Basic)
│   ├── OAuthClient.php     # Flujo OAuth 2.0 (3LO) de Atlassian
│   └── WorklogReport.php
├── templates/
│   └── report.php          # Vista del reporte
├── .env.example
├── .env                    # No commitear (en .gitignore)
├── composer.json
└── README.md
```

## Seguridad

- **OAuth**: los tokens viven en `$_SESSION` (cookie HttpOnly + Secure si HTTPS). Nunca llegan al navegador. El refresh token se rota automáticamente en cada uso.
- **API token**: el token vive en `localStorage` del navegador y se envía vía cookies. Más expuesto a XSS — preferir OAuth si es posible.
- No expongas la carpeta raíz del proyecto como document root; solo `public/`.
- El API token y los tokens OAuth tienen los mismos permisos que tu usuario en Jira; no los compartas.


