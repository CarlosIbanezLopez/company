<?php

/*
| Capa 2 - Lógica de negocio: API RESTful (prefijo /api, respuestas JSON).
| La autenticación usa tokens Bearer de Laravel Sanctum (guard "sanctum").
|
|   routes/api/sitio.php  -> endpoints públicos del portal (contenido, búsqueda, contador de visitas)
|   routes/api/auth.php   -> inicio/cierre de sesión, perfil, preferencias y menú dinámico
|   routes/api/admin.php  -> panel de administración protegido por la matriz de acceso (RBAC)
*/
require __DIR__.'/api/sitio.php';
require __DIR__.'/api/auth.php';
require __DIR__.'/api/admin.php';
