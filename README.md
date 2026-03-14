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

### 3. Configurar variables de entorno

Copia el archivo de ejemplo y edítalo con tus datos:

```bash
cp .env.example .env
```

Edita `.env` y configura:

| Variable        | Descripción |
|----------------|-------------|
| `JIRA_BASE_URL` | URL de tu instancia Jira (ej: `https://tu-empresa.atlassian.net`) |
| `JIRA_EMAIL`    | Email de tu cuenta de Jira/Atlassian |
| `JIRA_API_TOKEN`| Token de API de Atlassian (ver más abajo) |
| `TIMEZONE`      | Zona horaria (ej: `America/Mexico_City`) |
| `HOURS_PER_DAY` | Horas por día para el cálculo de jornada (por defecto: 8) |

### 4. Obtener el API Token de Jira

1. Entra en [Atlassian Account Settings](https://id.atlassian.com/manage-profile/security/api-tokens).
2. Inicia sesión con tu cuenta de Atlassian.
3. Pulsa **Create API token**, pon un nombre (ej: "Batman Worklog") y copia el token.
4. Pega el valor en `JIRA_API_TOKEN` en tu `.env`. No lo compartas ni lo subas al repositorio.

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

Ejemplo mínimo Nginx:

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

## Uso

1. Abre en el navegador la URL donde está desplegada la app (ej: `http://localhost:8080` o tu dominio).
2. Si no configuraste `.env`, la primera vez te pedirá **configuración** (URL de Jira, email y API token). Puedes guardarlos en cookies desde la interfaz para no usar `.env`.
3. El reporte muestra por defecto el **mes actual**. Puedes cambiar el rango con los filtros:
   - **Hoy** – solo el día actual.
   - **Semana** – lunes a viernes de la semana actual (hasta hoy).
   - **Mes** – desde el día 1 del mes hasta hoy.
   - **Personalizado** – elige fechas de inicio y fin.
4. En la parte superior verás el resumen (total de horas, diferencia frente a la jornada configurada) y, día a día, los worklogs con issue, resumen, proyecto y tiempo.
5. El botón de **configuración** (engranaje) permite cambiar credenciales y guardarlas en cookies sin tocar el `.env`.

## Estructura del proyecto

```
jira-batman/
├── public/
│   └── index.php      # Entrada web
├── src/
│   ├── JiraClient.php # Cliente API Jira
│   └── WorklogReport.php
├── templates/
│   └── report.php     # Vista del reporte
├── .env.example
├── .env               # No commitear (en .gitignore)
├── composer.json
└── README.md
```

## Seguridad

- No subas el archivo `.env` al repositorio (está en `.gitignore`).
- No expongas la carpeta raíz del proyecto como document root; solo `public/`.
- El API token tiene los mismos permisos que tu usuario en Jira; trátalo como una contraseña.

## Licencia

Proyecto de uso interno / según la política de tu organización.
