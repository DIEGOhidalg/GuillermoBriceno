# Briceño Canales — Landing Page

Landing page de conversión para el Estudio Jurídico Penal Briceño Canales.

---

## Estructura de archivos

```
/
├── index.php               ← Página principal
├── css/styles.css          ← Estilos (variables, componentes, responsive)
├── js/main.js              ← Navbar scroll, flip-cards, formulario AJAX
├── php/enviar.php          ← Manejador de formulario (backend)
├── uploads/                ← Adjuntos del formulario (bloqueados para ejecución)
│   └── .htaccess
└── assets/
    ├── img/                ← Imágenes de identidad
    └── icons/              ← Iconos de servicios
```

---

## Configuración inicial

### 1. Correo de destino (`php/enviar.php`)

Abre `php/enviar.php` y ajusta la línea:

```php
$destinatario = 'contacto@bricenocanales.com';
```

Si tu hosting no entrega bien con `mail()` nativa (problema común en hosting compartido),
instala **PHPMailer** y usa el bloque SMTP comentado al final del archivo:

```bash
composer require phpmailer/phpmailer
```

Luego ajusta `Host`, `Username` y `Password` con las credenciales SMTP de tu cuenta de correo.
**Nunca comites la clave SMTP en el repositorio** — usa una variable de entorno o un archivo `.env` excluido de git.

### 2. Permisos de la carpeta `/uploads/`

El servidor debe poder escribir en `/uploads/`. En Linux/Apache:

```bash
chmod 755 uploads/
```

Si el servidor corre como `www-data` o usuario diferente al tuyo:

```bash
chown www-data:www-data uploads/
```

Verifica que el `.htaccess` dentro de `/uploads/` esté activo (requiere `AllowOverride All` o `AllowOverride FileInfo Options` en la configuración de Apache).

### 3. PHP requerido

- PHP 7.4 o superior (se usa `match`, `declare(strict_types)`, `random_bytes`)
- Extensión `fileinfo` habilitada (para validar MIME real del adjunto)

---

## Assets — Manifiesto de reemplazo

Los archivos de assets ya están copiados desde los originales. Si necesitas reemplazar alguno:

| Ruta en el proyecto           | Descripción                              |
|-------------------------------|------------------------------------------|
| `assets/img/logo.png`         | Logo del estudio (navbar, footer, sello) |
| `assets/img/santiago.jpg`     | Fondo del hero                           |
| `assets/img/penal.jpg`        | Imagen lateral sección "Sobre el estudio"|
| `assets/img/penal-2.jpg`      | Imagen de galería del estudio            |
| `assets/img/penal-3.jpg`      | Imagen de galería del estudio            |
| `assets/img/penal-4.jpg`      | Imagen de galería del estudio            |
| `assets/img/penal-5.jpg`      | Imagen de galería del estudio            |
| `assets/img/ux-1.png`         | Ornamento decorativo del hero            |
| `assets/img/ux-2.png`         | Separador gráfico entre secciones        |
| `assets/img/ux-3.png`         | Fondo decorativo sección formulario      |
| `assets/icons/icono-1.png`    | Ícono: Eliminación de antecedentes       |
| `assets/icons/icono-2.png`    | Ícono: Revisión de requisitos y plazos   |
| `assets/icons/icono-3.png`    | Ícono: Querellas estratégicas            |
| `assets/icons/icono-4.png`    | Ícono: Defensa penal de alta complejidad |
| `assets/icons/icono-5.png`    | Ícono: Responsabilidad penal de empresas |
| `assets/icons/icono-6.png`    | Ícono: Estrategia reputacional           |
| `assets/img/abogado-1.jpg`    | **TODO:** Foto Guillermo Briceño         |
| `assets/img/abogado-2.jpg`    | **TODO:** Foto Matías Canales            |

Los dos últimos (`abogado-1.jpg` / `abogado-2.jpg`) aún no existen — al agregarlos,
las tarjetas de abogados los mostrarán automáticamente (tienen `onerror` de fallback).

---

## Testimonios

Los testimonios actuales son placeholders. Antes de publicar, reemplaza el bloque
`#carruselTestimonios` con testimonios reales que cuenten con **autorización expresa escrita**
del cliente. No publicar nombres completos ni datos identificables.

---

## LinkedIn

En el footer hay un enlace de LinkedIn con `href="#"`. Reemplázalo con la URL real del
perfil de LinkedIn del estudio.

---

## Checklist antes de subir a producción

- [ ] Ajustar correo destino en `php/enviar.php`
- [ ] Configurar SMTP (PHPMailer recomendado para adjuntos)
- [ ] Subir fotos reales de los abogados como `assets/img/abogado-1.jpg` y `abogado-2.jpg`
- [ ] Actualizar URL de LinkedIn en el footer
- [ ] Verificar permisos de `/uploads/` en el servidor
- [ ] Reemplazar testimonios con versiones autorizadas
- [ ] Agregar certificado SSL (HTTPS obligatorio para formularios)
- [ ] Probar envío de formulario con y sin adjunto
- [ ] Verificar que el `.htaccess` de `/uploads/` bloquea ejecución de scripts
