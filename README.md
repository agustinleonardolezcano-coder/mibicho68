# Sistema Cooperativa — Fray Luis Beltrán

## 🚀 Instrucciones de Despliegue en AeonFree / cPanel

### 1. Subir archivos
- Panel: `cpanel.aeonfree.com`
- Carpeta destino: `htdocs/` (raíz del dominio)
- Subí **todos los archivos** manteniendo la estructura de carpetas

### 2. Estructura de carpetas requerida
```
htdocs/
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── donate.php
├── profile.php
├── receipt.php
├── logout.php
├── forgot-password.php
├── reset-password.php
├── install.php          ← ejecutar UNA VEZ, luego eliminar
├── install.sql
├── config.php
├── db.php
├── auth.php
├── functions.php
├── .htaccess
├── assets/
│   ├── style.css
│   ├── admin.css
│   ├── app.js
│   └── logo.png
├── admin/
│   ├── index.php
│   ├── users.php
│   ├── donations.php
│   ├── campaigns.php
│   ├── admins.php
│   ├── export.php
│   ├── settings.php
│   └── partials/
│       ├── sidebar.php
│       └── topbar.php
└── api/
    ├── mp_webhook.php
    └── mp_return.php
```

### 3. Primer uso
1. Abrí `https://tudominio.com/install.php`
2. Completá nombre, email y contraseña del primer administrador
3. ✅ El sistema creará las tablas automáticamente
4. **¡IMPORTANTE!** Eliminá `install.php` y `install.sql` del servidor

### 4. Configurar Webhook de MercadoPago
En el panel de MercadoPago Developers, configurá la URL de webhook:
```
https://tudominio.com/api/mp_webhook.php
```
Eventos a suscribir: `payment`

### 5. Modo de producción
Cuando tengas credenciales reales de MP:
- En `config.php`: cambiá `MP_MODE` a `production`
- Reemplazá las keys TEST por las de producción

### 6. Flujo del sistema
1. Donante se registra → queda en estado "pendiente"
2. Admin aprueba el registro
3. Donante puede hacer donaciones vía MercadoPago
4. MP genera una notificación al webhook → donación se aprueba automáticamente
5. Admin también puede aprobar/rechazar manualmente
6. Donante descarga comprobante en PDF

---
Desarrollado con ♥ para la Cooperativa Fray Luis Beltrán
