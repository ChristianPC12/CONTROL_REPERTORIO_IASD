# Launcher de Windows (Go)

Ejecutable que empaqueta la app como aplicación de escritorio: arranca el
servidor PHP local (`php -S`), abre el navegador por defecto y vive en la
bandeja del sistema. Instancia única; al salir cierra el servidor.

## Estructura del distribuible

```
Control de Repertorio.exe        <- este launcher
runtime/
  php/                           <- PHP portable (php.exe, php.ini, ext/)
  app/                           <- código de la app (index.php, api.php, ...)
```

Los datos escribibles (configuración, descargas, logs) se guardan en
`%LOCALAPPDATA%\ControlMusica` (no dentro de la instalación).

## Compilar

Requisitos: Go 1.21+.

```sh
# Metadatos + icono en el .exe
go install github.com/josephspurrier/goversioninfo/cmd/goversioninfo@latest
goversioninfo -64 -o resource_windows_amd64.syso versioninfo.json

# Compilar sin ventana de consola
go build -ldflags "-H windowsgui -s -w" -o "Control de Repertorio.exe" .
```

## Runtime PHP portable

1. Descargar PHP NTS x64 de <https://windows.php.net/download/>.
2. Copiar `php.ini` (habilita `mbstring`, `openssl`, `fileinfo`, `curl`).
3. Opcional: quitar de `ext/` las extensiones no usadas y los DLLs de ICU
   (`icu*72.dll`) para reducir el tamaño.

El binario firmado se produce con el pipeline de SignPath (ver
`code-signing-policy.md`).
