; Instalador de Control de Repertorio (Inno Setup)
; Instala por-usuario (sin admin). Crea accesos directos "Control de Repertorio"
; en Menu Inicio y Escritorio, con WorkingDir correcto para que el launcher
; siempre encuentre su carpeta runtime.

#define MyAppName "Control de Repertorio"
#define MyAppVersion "1.0.1"
#define MyAppPublisher "Iglesia Adventista del Septimo Dia"
#define MyAppExe "Control de Repertorio.exe"

[Setup]
AppId={{7E2C1A94-3B6D-4E29-9C3F-A15D0B7F10CD}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\{#MyAppName}
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
PrivilegesRequiredOverridesAllowed=dialog
OutputDir=C:\cm_build
OutputBaseFilename=Control-de-Repertorio-v{#MyAppVersion}-Setup
SetupIconFile=C:\cm_build\launcher\icon.ico
UninstallDisplayIcon={app}\{#MyAppExe}
UninstallDisplayName={#MyAppName}
WizardStyle=modern
Compression=lzma2
SolidCompression=yes
ArchitecturesInstallIn64BitMode=x64compatible
ArchitecturesAllowed=x64compatible

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: checkedonce

[Files]
Source: "C:\cm_build\dist\*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs ignoreversion

[Icons]
; Menu Inicio -> nombre limpio "Control de Repertorio" (sin .exe)
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExe}"; WorkingDir: "{app}"
Name: "{group}\Desinstalar {#MyAppName}"; Filename: "{uninstallexe}"
; Escritorio (opcional segun tarea)
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExe}"; WorkingDir: "{app}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExe}"; Description: "{cm:LaunchProgram,{#MyAppName}}"; WorkingDir: "{app}"; Flags: nowait postinstall skipifsilent
