package main

import (
	_ "embed"
	"fmt"
	"net"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"syscall"
	"time"

	"github.com/getlantern/systray"
)

//go:embed icon.ico
var iconData []byte

const appName = "Control de Repertorio"

// Puerto centinela para garantizar una sola instancia. Si está ocupado,
// significa que la app ya está corriendo.
const guardAddr = "127.0.0.1:52700"

var (
	phpCmd  *exec.Cmd
	appURL  string
	guardLn net.Listener
)

func main() {
	// Instancia única: intenta reservar el puerto centinela.
	ln, err := net.Listen("tcp", guardAddr)
	if err != nil {
		// Ya hay una instancia: abre su URL guardada y sal.
		if data, e := os.ReadFile(instanceFile()); e == nil && len(data) > 0 {
			openBrowser("http://" + string(data))
		}
		return
	}
	guardLn = ln

	if err := startServer(); err != nil {
		logLine("ERROR startServer: " + err.Error())
		guardLn.Close()
		return
	}

	openBrowser(appURL)
	systray.Run(onReady, onExit)
}

func onReady() {
	systray.SetIcon(iconData)
	systray.SetTitle(appName)
	systray.SetTooltip(appName + " — corriendo")

	mOpen := systray.AddMenuItem("Abrir "+appName, "Abrir en el navegador")
	systray.AddSeparator()
	mQuit := systray.AddMenuItem("Salir", "Cerrar la aplicación")

	go func() {
		for {
			select {
			case <-mOpen.ClickedCh:
				openBrowser(appURL)
			case <-mQuit.ClickedCh:
				systray.Quit()
				return
			}
		}
	}()
}

func onExit() {
	stopServer()
	if guardLn != nil {
		guardLn.Close()
	}
	os.Remove(instanceFile())
}

// ── Servidor PHP ─────────────────────────────────────────────────────

func startServer() error {
	dir, err := exeDir()
	if err != nil {
		return err
	}
	phpExe := filepath.Join(dir, "runtime", "php", "php.exe")
	phpIni := filepath.Join(dir, "runtime", "php", "php.ini")
	phpExt := filepath.Join(dir, "runtime", "php", "ext")
	docRoot := filepath.Join(dir, "runtime", "app")

	if _, err := os.Stat(phpExe); err != nil {
		return fmt.Errorf("no se encontró php.exe en %s", phpExe)
	}

	port, err := freePort()
	if err != nil {
		return err
	}
	addr := fmt.Sprintf("127.0.0.1:%d", port)
	appURL = "http://" + addr

	data := dataDir()
	if err := os.MkdirAll(data, 0o755); err != nil {
		return err
	}

	phpCmd = exec.Command(phpExe,
		"-c", phpIni,
		"-d", "extension_dir="+phpExt,
		"-S", addr,
		"-t", docRoot,
	)
	phpCmd.Dir = docRoot
	phpCmd.Env = append(os.Environ(), "CM_DATA_DIR="+data)
	// Sin ventana de consola para el proceso PHP.
	phpCmd.SysProcAttr = &syscall.SysProcAttr{HideWindow: true, CreationFlags: 0x08000000}

	if err := phpCmd.Start(); err != nil {
		return err
	}

	// Espera a que el servidor responda (máx 20 s).
	deadline := time.Now().Add(20 * time.Second)
	client := &http.Client{Timeout: 2 * time.Second}
	for time.Now().Before(deadline) {
		resp, err := client.Get(appURL + "/api.php?action=folders")
		if err == nil {
			resp.Body.Close()
			os.WriteFile(instanceFile(), []byte(addr), 0o644)
			return nil
		}
		time.Sleep(200 * time.Millisecond)
	}
	stopServer()
	return fmt.Errorf("timeout esperando al servidor PHP")
}

func stopServer() {
	if phpCmd != nil && phpCmd.Process != nil {
		phpCmd.Process.Kill()
	}
}

// ── Utilidades ───────────────────────────────────────────────────────

func exeDir() (string, error) {
	p, err := os.Executable()
	if err != nil {
		return "", err
	}
	return filepath.Dir(p), nil
}

func dataDir() string {
	base := os.Getenv("LOCALAPPDATA")
	if base == "" {
		base = os.Getenv("APPDATA")
	}
	if base == "" {
		d, _ := exeDir()
		base = d
	}
	return filepath.Join(base, "ControlMusica")
}

func instanceFile() string {
	return filepath.Join(dataDir(), "instance.txt")
}

func freePort() (int, error) {
	ln, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		return 0, err
	}
	defer ln.Close()
	return ln.Addr().(*net.TCPAddr).Port, nil
}

func openBrowser(url string) {
	cmd := exec.Command("rundll32", "url.dll,FileProtocolHandler", url)
	cmd.SysProcAttr = &syscall.SysProcAttr{HideWindow: true, CreationFlags: 0x08000000}
	cmd.Start()
}

func logLine(msg string) {
	os.MkdirAll(dataDir(), 0o755)
	f, err := os.OpenFile(filepath.Join(dataDir(), "launcher.log"),
		os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0o644)
	if err != nil {
		return
	}
	defer f.Close()
	f.WriteString(time.Now().Format(time.RFC3339) + "  " + msg + "\n")
}
