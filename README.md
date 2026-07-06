# Control de Repertorio

Gestor y reproductor de **repertorio** (videos, imágenes y playlists) para
presentaciones de adoración. Aplicación de escritorio **autocontenida**: un solo
ejecutable arranca un servidor local y abre la interfaz en el navegador. No
requiere instalar XAMPP, Apache ni PHP por separado.

## Características

- Organiza carpetas de videos e imágenes y las presenta al instante en la
  pantalla/monitor elegido.
- Reproductor nativo en Windows y reproductor web emergente.
- Playlists de reproducción automática.
- Descarga de videos por URL (YouTube, etc.) mediante `yt-dlp` + `ffmpeg`.
- Interfaz con **7 temas** de color y diseño de tamaño fijo (se ve igual en
  cualquier zoom o resolución).

## Cómo funciona

- **Frontend:** HTML + CSS + JavaScript puro (multiplataforma).
- **Backend:** PHP servido por el servidor integrado de PHP (`php -S`) — sin
  Apache. El ejecutable (launcher en Go) arranca PHP en un puerto local, abre el
  navegador por defecto y vive en la bandeja del sistema.
- **Datos escribibles** (configuración, descargas, logs) se guardan en la
  carpeta de datos del usuario, no dentro de la instalación.

> Nota de plataforma: el backend de reproducción nativa y descargas está
> implementado para **Windows** en esta versión. El port a macOS/Linux está
> planeado.

## Desarrollo

Requisitos: PHP 8.3+ (para desarrollo puede usarse el de XAMPP).

```sh
# Servir la app con el servidor integrado de PHP
php -S 127.0.0.1:8733 -t .
# Abrir http://127.0.0.1:8733/
```

El empaquetado como ejecutable (launcher en Go + PHP portable) está en
[`packaging/`](packaging/).

## Firma de código (Code signing policy)

Este proyecto usa firma de código gratuita para software de código abierto.
Consulta la política en [`code-signing-policy.md`](code-signing-policy.md).

Free code signing provided by [SignPath.io](https://signpath.io), certificate
by [SignPath Foundation](https://signpath.org).

## Licencia

[MIT](LICENSE) © 2026 Christian Paniagua
