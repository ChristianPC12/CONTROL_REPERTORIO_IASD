// ─── Lienzo fijo ──────────────────────────────────────────────────────────────
// La interfaz se diseña siempre a 1389px de ancho y se escala completa al
// tamaño real de la ventana. Resultado: se ve EXACTAMENTE igual con cualquier
// zoom del navegador o dimensión de ventana.
const APP_DESIGN_WIDTH = 1389;

function fitAppToViewport() {
  const scale = window.innerWidth / APP_DESIGN_WIDTH;
  if (!scale || !isFinite(scale)) return;

  const body = document.body;
  body.style.width = APP_DESIGN_WIDTH + 'px';
  body.style.height = (window.innerHeight / scale) + 'px';
  body.style.transformOrigin = '0 0';
  body.style.transform = 'scale(' + scale + ')';
}

fitAppToViewport();
window.addEventListener('resize', fitAppToViewport);

const folderSelect = document.getElementById('folderSelect');
const searchInput = document.getElementById('searchInput');
const statusText = document.getElementById('statusText');
const videoCount = document.getElementById('videoCount');
const grid = document.getElementById('grid');
const template = document.getElementById('videoCardTemplate');
const monitorSelect = document.getElementById('monitorSelect');
const mediaTypeSelect = document.getElementById('mediaTypeSelect');
const playAllBtn = document.getElementById('playAllBtn');
const addFolderBtn = document.getElementById('addFolderBtn');
const renameMediaBtn = document.getElementById('renameMediaBtn');
const tempPlaylistBtn = document.getElementById('tempPlaylistBtn');
const viewPlaylistsBtn = document.getElementById('viewPlaylistsBtn');

const controllerModal = document.getElementById('controllerModal');
const songTitle = document.getElementById('songTitle');
const currentTimeEl = document.getElementById('currentTime');
const durationEl = document.getElementById('duration');
const progressBar = document.getElementById('progressBar');
const playPauseBtn = document.getElementById('playPauseBtn');
const closeBtn = document.getElementById('closeBtn');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

const downloadVideoBtn = document.getElementById('downloadVideoBtn');
const downloadModal = document.getElementById('downloadModal');
const downloadFolderSelect = document.getElementById('downloadFolderSelect');
const downloadUrlInput = document.getElementById('downloadUrlInput');
const downloadTypeSelect = document.getElementById('downloadTypeSelect');
const downloadStartBtn = document.getElementById('downloadStartBtn');
const downloadCancelBtn = document.getElementById('downloadCancelBtn');
const downloadProgressWrap = document.getElementById('downloadProgressWrap');
const downloadProgressBar = document.getElementById('downloadProgressBar');
const downloadPercent = document.getElementById('downloadPercent');
const downloadProgressLabel = document.getElementById('downloadProgressLabel');
const downloadProgressDetail = document.getElementById('downloadProgressDetail');
const downloadLogTail = document.getElementById('downloadLogTail');
const downloadStatus = document.getElementById('downloadStatus');
const downloadHelpBtn = document.getElementById('downloadHelpBtn');
const downloadHelpModal = document.getElementById('downloadHelpModal');
const downloadHelpCloseBtn = document.getElementById('downloadHelpCloseBtn');
const folderAccessModal = document.getElementById('folderAccessModal');
const imageViewerOverlay = document.getElementById('imageViewerOverlay');
const imageViewerImg = document.getElementById('imageViewerImg');
const folderPathInput = document.getElementById('folderPathInput');
const folderAccessChooseBtn = document.getElementById('folderAccessChooseBtn');
const folderAccessCancelBtn = document.getElementById('folderAccessCancelBtn');
const renameModal = document.getElementById('renameModal');
const renameFolderSelect = document.getElementById('renameFolderSelect');
const renameTypeSelect = document.getElementById('renameTypeSelect');
const renameFileSelect = document.getElementById('renameFileSelect');
const renameNameInput = document.getElementById('renameNameInput');
const renameExtensionLabel = document.getElementById('renameExtensionLabel');
const renameStatus = document.getElementById('renameStatus');
const renameCancelBtn = document.getElementById('renameCancelBtn');
const renameSaveBtn = document.getElementById('renameSaveBtn');
const renameFilePickerBtn = document.getElementById('renameFilePickerBtn');
const renameFilePickerLabel = document.getElementById('renameFilePickerLabel');
const renameFilePickerModal = document.getElementById('renameFilePickerModal');
const renameFilePickerCloseBtn = document.getElementById('renameFilePickerCloseBtn');
const renameFilePickerSearch = document.getElementById('renameFilePickerSearch');
const renameFilePickerList = document.getElementById('renameFilePickerList');
const renameFilePickerEmpty = document.getElementById('renameFilePickerEmpty');
const confirmModal = document.getElementById('confirmModal');
const confirmModalTitle = document.getElementById('confirmModalTitle');
const confirmModalText = document.getElementById('confirmModalText');
const confirmModalOkBtn = document.getElementById('confirmModalOkBtn');
const confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');

let confirmDialogResolve = null;

function closeConfirmDialog(result) {
  if (!confirmModal) return;
  confirmModal.classList.remove('active');
  confirmModal.setAttribute('aria-hidden', 'true');

  if (confirmDialogResolve) {
    const resolve = confirmDialogResolve;
    confirmDialogResolve = null;
    resolve(result);
  }
}

function showConfirmDialog(message, options) {
  const settings = options || {};

  if (!confirmModal || !confirmModalText) {
    return Promise.resolve(window.confirm(message));
  }

  // Si hubiera un diálogo pendiente, se resuelve como cancelado.
  closeConfirmDialog(false);

  if (confirmModalTitle) confirmModalTitle.textContent = settings.title || 'Confirmar';
  confirmModalText.textContent = message;
  if (confirmModalOkBtn) confirmModalOkBtn.textContent = settings.okLabel || 'Confirmar';
  if (confirmModalCancelBtn) {
    // hideCancel => diálogo de un solo botón (aviso), no confirmación.
    if (settings.hideCancel) {
      confirmModalCancelBtn.style.display = 'none';
    } else {
      confirmModalCancelBtn.style.display = '';
      confirmModalCancelBtn.textContent = settings.cancelLabel || 'Cancelar';
    }
  }
  confirmModal.classList.toggle('single-action', Boolean(settings.hideCancel));

  confirmModal.classList.add('active');
  confirmModal.setAttribute('aria-hidden', 'false');

  return new Promise(function (resolve) {
    confirmDialogResolve = resolve;
    setTimeout(function () {
      var focusBtn = settings.hideCancel ? confirmModalOkBtn : confirmModalCancelBtn;
      if (focusBtn) focusBtn.focus();
    }, 40);
  });
}
const tempPlaylistModal = document.getElementById('tempPlaylistModal');
const tempPlaylistFolderSelect = document.getElementById('tempPlaylistFolderSelect');
const tempPlaylistSearchInput = document.getElementById('tempPlaylistSearchInput');
const tempPlaylistList = document.getElementById('tempPlaylistList');
const tempPlaylistStatus = document.getElementById('tempPlaylistStatus');
const tempPlaylistCancelBtn = document.getElementById('tempPlaylistCancelBtn');
const tempPlaylistClearBtn = document.getElementById('tempPlaylistClearBtn');
const tempPlaylistSaveBtn = document.getElementById('tempPlaylistSaveBtn');
const playlistNameModal = document.getElementById('playlistNameModal');
const playlistNameInput = document.getElementById('playlistNameInput');
const playlistNameStatus = document.getElementById('playlistNameStatus');
const playlistNameCancelBtn = document.getElementById('playlistNameCancelBtn');
const playlistNameSaveBtn = document.getElementById('playlistNameSaveBtn');
const playlistsModal = document.getElementById('playlistsModal');
const playlistsList = document.getElementById('playlistsList');
const playlistsStatus = document.getElementById('playlistsStatus');
const playlistsCloseBtn = document.getElementById('playlistsCloseBtn');
const playlistDetailModal = document.getElementById('playlistDetailModal');
const playlistDetailTitle = document.getElementById('playlistDetailTitle');
const playlistDetailMeta = document.getElementById('playlistDetailMeta');
const playlistDetailMonitorSelect = document.getElementById('playlistDetailMonitorSelect');
const playlistDetailList = document.getElementById('playlistDetailList');
const playlistDetailCloseBtn = document.getElementById('playlistDetailCloseBtn');
const playlistDeleteBtn = document.getElementById('playlistDeleteBtn');
const noticeToast = document.getElementById('noticeToast');
const noticeToastIcon = document.getElementById('noticeToastIcon');
const noticeToastText = document.getElementById('noticeToastText');
const fileCountLabel = document.getElementById('fileCountLabel');

const localAudioPlayer = document.createElement('video');
localAudioPlayer.preload = 'auto';
localAudioPlayer.setAttribute('playsinline', '');
localAudioPlayer.style.display = 'none';
document.body.appendChild(localAudioPlayer);

let currentFolder = '';
let library = [];
let currentIndex = -1;
let currentMediaType = 'video';
let isPaused = false;
let isPlayAllMode = false;
let playbackMode = '';
let activeLocalAudioUrl = '';
let activeImageViewerUrl = '';
let tempPlaylistItems = [];
let tempPlaylistCandidateItems = [];
let tempPlaylistSelectedKeys = new Set();
let renameMediaItems = [];
let pendingPlaylistItems = [];
let savedPlaylists = [];
let activePlaylistDetail = null;
let playlistReturnContext = null;

let detectedScreens = null;
let foldersCache = [];
let activeDownloadJob = null;
let activeDownloadFolder = '';
let downloadPollTimer = null;
let downloadProgressFrame = null;
let downloadProgressValue = 0;
let downloadLiveProgressFrame = null;
let downloadLiveProgressLastTick = 0;
let downloadServerProgressValue = 0;
let downloadLiveProgressCap = 0;
let downloadStatusTimer = null;
let folderAccessResolve = null;
let noticeToastTimer = null;

const MONITOR_PREF_KEY = 'control-musica.monitor';
const FOLDER_PREF_KEY = 'control-musica.folder';
const FILE_TYPE_PREF_KEY = 'control-musica.fileType';
const FOLDER_RECORDS_KEY = 'control-musica.folderRecords.v1';
const FOLDER_DB_NAME = 'control-musica-folders';
const FOLDER_DB_STORE = 'handles';
const TEMP_PLAYLIST_ID = '__temp_playlist__';
const channel = new BroadcastChannel('control-musica');
const PLAY_ICON = '\u25B6';
const PAUSE_ICON = '\u23F8';
const PREV_ICON = '\u23EE';
const NEXT_ICON = '\u23ED';
const CLOSE_ICON = '\u2715';

const DEBUG_API = false;

currentMediaType = localStorage.getItem(FILE_TYPE_PREF_KEY) === 'image' ? 'image' : 'video';
if (mediaTypeSelect) mediaTypeSelect.value = currentMediaType;
if (prevBtn) prevBtn.textContent = PREV_ICON;
if (nextBtn) nextBtn.textContent = NEXT_ICON;
if (closeBtn) closeBtn.textContent = CLOSE_ICON;
if (playPauseBtn) setPlayPauseButton(false, false);

// ─── API helper ──────────────────────────────────────────────────────────────

async function apiJson(url, options) {
  let response;
  let rawText = '';

  try {
    response = await fetch(url, options || {});
    rawText = await response.text();

    if (DEBUG_API) {
      console.groupCollapsed('[API] ' + (options?.method || 'GET') + ' → ' + url);
      console.log('HTTP status:', response.status);
      console.log('Respuesta cruda de PHP:', rawText);
      console.groupEnd();
    }

    if (!rawText || !rawText.trim()) {
      throw new Error('PHP respondió vacío.');
    }

    let data;
    try {
      data = JSON.parse(rawText);
    } catch (jsonError) {
      throw new Error('PHP no devolvió JSON válido.');
    }

    if (!response.ok) {
      throw new Error(data.error || 'Error HTTP ' + response.status);
    }

    return data;
  } catch (error) {
    throw error;
  }
}

// ─── Monitor / pantalla ───────────────────────────────────────────────────────

function getFallbackTargets() {
  const left = window.screen.availLeft ?? 0;
  const top = window.screen.availTop ?? 0;
  // Para presentación real usamos el tamaño completo del monitor, no availWidth/availHeight.
  // avail* puede descontar barra de tareas y deja franjas.
  const width = window.screen.width || window.screen.availWidth;
  const height = window.screen.height || window.screen.availHeight;

  return [
    {
      value: 'main',
      label: 'Monitor 1 - Principal (' + width + 'x' + height + ')',
      left: left,
      top: top,
      width: width,
      height: height
    }
  ];
}

function normalizeScreenTarget(raw, index) {
  const fallback = getFallbackTargets()[0];
  const width = Number(raw.width || raw.availWidth || fallback.width) || fallback.width;
  const height = Number(raw.height || raw.availHeight || fallback.height) || fallback.height;
  const isPrimary = Boolean(raw.primary || raw.isPrimary || raw.value === 'main' || index === 0);

  return {
    value: raw.value || (isPrimary ? 'main' : (index === 1 ? 'right' : 'screen_' + (index + 1))),
    label: raw.label || (
      isPrimary
        ? 'Monitor 1 - Principal (' + width + 'x' + height + ')'
        : 'Monitor ' + (index + 1) + ' (' + width + 'x' + height + ')'
    ),
    left: Number(raw.left ?? raw.availLeft ?? fallback.left) || 0,
    top: Number(raw.top ?? raw.availTop ?? fallback.top) || 0,
    width: width,
    height: height,
    source: raw.source || 'browser'
  };
}

function ensureSecondaryTarget(targets) {
  targets = Array.isArray(targets) ? targets.filter(Boolean) : [];

  if (!targets.length) return getFallbackTargets();
  return targets;
}

async function fetchWindowsScreenTargets() {
  try {
    const data = await apiJson('api.php?action=screens');

    if (!data || !Array.isArray(data.screens) || !data.screens.length) return [];

    return data.screens.map(normalizeScreenTarget);
  } catch (e) {
    console.warn('[Monitor] detección Windows no disponible:', e.message);
    return [];
  }
}

async function fetchBrowserScreenTargets() {
  if ('getScreenDetails' in window) {
    try {
      await requestWindowMgmtPermission();
      const details = await window.getScreenDetails();
      const screens = details.screens;
      detectedScreens = screens;

      const primary = screens.find(s => s.isPrimary) || screens[0];
      const secondary = screens.length > 1
        ? (screens.find(s => !s.isPrimary) || screens[1])
        : null;

      const targets = [
        {
          value: 'main',
          label: 'Monitor 1 - Principal (' + (primary.width || primary.availWidth) + 'x' + (primary.height || primary.availHeight) + ')',
          left: primary.left ?? primary.availLeft,
          top: primary.top ?? primary.availTop,
          width: primary.width || primary.availWidth,
          height: primary.height || primary.availHeight
        }
      ];

      if (secondary) {
        targets.push({
          value: 'right',
          label: 'Monitor 2 (' + (secondary.width || secondary.availWidth) + 'x' + (secondary.height || secondary.availHeight) + ')',
          left: secondary.left ?? secondary.availLeft,
          top: secondary.top ?? secondary.availTop,
          width: secondary.width || secondary.availWidth,
          height: secondary.height || secondary.availHeight
        });
      }

      return targets.map(normalizeScreenTarget);
    } catch (e) {
      console.warn('[Monitor] getScreenDetails no disponible:', e.message);
    }
  }

  return [];
}

async function fetchScreenTargets() {
  const windowsTargets = await fetchWindowsScreenTargets();

  if (windowsTargets.length > 1) {
    return windowsTargets;
  }

  const browserTargets = await fetchBrowserScreenTargets();

  if (browserTargets.length > 1) {
    return browserTargets;
  }

  return ensureSecondaryTarget(browserTargets.length ? browserTargets : (windowsTargets.length ? windowsTargets : getFallbackTargets()));
}

let monitorTargets = getFallbackTargets();
let lastMonitorSignature = '';

function monitorTargetsSignature(targets) {
  return (targets || []).map(function (target) {
    return [
      target.value,
      target.left,
      target.top,
      target.width,
      target.height,
      target.label
    ].join(':');
  }).join('|');
}

async function renderMonitorOptions(options) {
  const settings = options || {};
  const previousTargets = monitorTargets.slice();
  const previousValue = monitorSelect.value || localStorage.getItem(MONITOR_PREF_KEY) || 'main';

  if (!settings.silent) {
    monitorSelect.innerHTML = '<option value="main">Monitor 1 - Principal (detectando...)</option>';
  }

  const targets = await fetchScreenTargets();
  const nextSignature = monitorTargetsSignature(targets);
  const hadSignature = Boolean(lastMonitorSignature);
  const changed = nextSignature !== lastMonitorSignature;

  monitorTargets = targets;

  if (!settings.silent || changed) {
    monitorSelect.innerHTML = '';

    for (const s of targets) {
      const o = document.createElement('option');
      o.value = s.value;
      o.textContent = s.label;
      monitorSelect.appendChild(o);
    }
  }

  const fallbackValue = targets.some(function (s) { return s.value === 'main'; })
    ? 'main'
    : (targets[0] ? targets[0].value : '');

  if (previousValue && targets.some(function (s) { return s.value === previousValue; })) {
    monitorSelect.value = previousValue;
  } else {
    monitorSelect.value = fallbackValue;
    localStorage.setItem(MONITOR_PREF_KEY, fallbackValue);

    if (settings.notice && hadSignature && changed && previousValue && previousValue !== fallbackValue) {
      showAutoNotice('La pantalla seleccionada ya no está disponible.', 'warning');
    }

    if (isPlayerOpen()) {
      positionPlayerWindow('monitor_removed');
    }
  }

  if (settings.notice && hadSignature && changed && previousValue === monitorSelect.value) {
    const previousCount = previousTargets.length;
    const nextCount = targets.length;

    if (nextCount > previousCount) {
      showAutoNotice('Pantallas actualizadas: se detectó una nueva pantalla.', 'info');
    } else if (nextCount < previousCount) {
      showAutoNotice('Pantallas actualizadas: una pantalla ya no está disponible.', 'warning');
    }
  }

  lastMonitorSignature = nextSignature;
  return { changed: changed, targets: targets };
}

// ─── Helpers de formato ───────────────────────────────────────────────────────

function prettySize(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const p = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  const v = bytes / Math.pow(1024, p);
  return v.toFixed(v >= 10 || p === 0 ? 0 : 1) + ' ' + units[p];
}

function formatTime(s) {
  if (!Number.isFinite(s) || s <= 0) return '00:00';
  return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(Math.floor(s % 60)).padStart(2, '0');
}

function buildMediaUrl(item) {
  // CM_MEDIA_BASE: en la app empaquetada el streaming va por un servidor php
  // dedicado (otro puerto) para no bloquear la API de control (php -S atiende
  // una petición a la vez en Windows). En Apache/dev queda mismo origen.
  return (window.CM_MEDIA_BASE || '') + 'api.php?action=play'
    + '&folder=' + encodeURIComponent(item.folder)
    + '&file=' + encodeURIComponent(item.name);
}

function mediaItemKey(item) {
  return [item && item.folder || '', item && item.name || ''].join('|');
}

function isMainMonitorSelected() {
  return getSelectedMonitorTargetSync().value === 'main';
}

function shouldUseLocalAudio(item) {
  const kind = item && (item.kind || currentMediaType);
  return kind !== 'image' && isMainMonitorSelected();
}

function shouldUseInlineImageViewer(item) {
  const kind = item && (item.kind || currentMediaType);
  return kind === 'image' && isMainMonitorSelected();
}

function isLocalAudioActive() {
  return playbackMode === 'local';
}

function isVideoFilename(filename) {
  return /\.(mp4|mkv|webm|avi|mov|wmv|flv|m4v|mpg|mpeg|3gp|ts|mts|m2ts)$/i.test(filename || '');
}

function isImageFilename(filename) {
  return /\.(jpe?g|png|webp|gif|svg|bmp|tiff?|heic|heif|avif)$/i.test(filename || '');
}

function mediaLabels() {
  return currentMediaType === 'image'
    ? { singular: 'imagen', plural: 'imágenes', action: 'mostrarla', visible: 'imágenes visibles' }
    : { singular: 'video', plural: 'videos', action: 'reproducirlo', visible: 'videos visibles' };
}

function getVisibleLibraryCount() {
  const query = searchInput.value.trim();
  return query
    ? library.filter(function (i) { return fuzzyMatch(i.title, query); }).length
    : library.length;
}

function setIdleStatus(message) {
  if (message) {
    statusText.textContent = String(message).includes('coincidencias')
      ? 'No hay coincidencias con la b\u00fasqueda.'
      : message;
    return;
  }

  const labels = mediaLabels();

  if (!currentFolder) {
    statusText.textContent = 'Selecciona una carpeta para ver las ' + labels.plural + '.';
    return;
  }

  if (!library.length) {
    statusText.textContent = 'No hay ' + labels.plural + ' compatibles en esta carpeta.';
    return;
  }

  const visible = getVisibleLibraryCount();
  if (!visible) {
    statusText.textContent = 'No hay coincidencias con la busqueda.';
    return;
  }

  statusText.textContent = 'Listo. Selecciona ' + (labels.singular === 'imagen' ? 'una ' : 'un ') + labels.singular + ' para ' + labels.action + '.';
}

function updateMediaTypeUi() {
  const labels = mediaLabels();
  if (fileCountLabel) fileCountLabel.textContent = labels.visible;
  if (searchInput) searchInput.placeholder = currentMediaType === 'image' ? 'Escribe un nombre...' : 'Escribe un título...';

  const isImageMode = currentMediaType === 'image';
  [playAllBtn, tempPlaylistBtn, viewPlaylistsBtn].forEach(function (btn) {
    if (!btn) return;
    btn.classList.toggle('hidden-action', isImageMode);
    btn.disabled = isImageMode;
  });
}

function titleFromFilename(filename) {
  return String(filename || '').replace(/\.[^.]+$/, '');
}

function supportsFolderPicker() {
  return 'showDirectoryPicker' in window && 'indexedDB' in window;
}

function makeBrowserFolderId() {
  return 'fs_' + Date.now().toString(36) + '_' + Math.random().toString(16).slice(2, 10);
}

function openFolderDb() {
  return new Promise(function (resolve, reject) {
    const request = indexedDB.open(FOLDER_DB_NAME, 1);

    request.onupgradeneeded = function () {
      const db = request.result;
      if (!db.objectStoreNames.contains(FOLDER_DB_STORE)) {
        db.createObjectStore(FOLDER_DB_STORE);
      }
    };

    request.onsuccess = function () {
      resolve(request.result);
    };

    request.onerror = function () {
      reject(request.error || new Error('No se pudo abrir la base interna de carpetas.'));
    };
  });
}

async function saveFolderHandle(id, handle) {
  const db = await openFolderDb();

  try {
    await new Promise(function (resolve, reject) {
      const tx = db.transaction(FOLDER_DB_STORE, 'readwrite');
      tx.objectStore(FOLDER_DB_STORE).put(handle, id);
      tx.oncomplete = resolve;
      tx.onerror = function () {
        reject(tx.error || new Error('No se pudo guardar la carpeta seleccionada.'));
      };
    });
  } finally {
    db.close();
  }
}

async function getFolderHandle(id) {
  const db = await openFolderDb();

  try {
    return await new Promise(function (resolve, reject) {
      const tx = db.transaction(FOLDER_DB_STORE, 'readonly');
      const request = tx.objectStore(FOLDER_DB_STORE).get(id);
      request.onsuccess = function () {
        resolve(request.result || null);
      };
      request.onerror = function () {
        reject(request.error || new Error('No se pudo recuperar la carpeta.'));
      };
    });
  } finally {
    db.close();
  }
}

async function clearFolderHandles() {
  const db = await openFolderDb();

  try {
    await new Promise(function (resolve, reject) {
      const tx = db.transaction(FOLDER_DB_STORE, 'readwrite');
      tx.objectStore(FOLDER_DB_STORE).clear();
      tx.oncomplete = resolve;
      tx.onerror = function () {
        reject(tx.error || new Error('No se pudieron limpiar las carpetas guardadas.'));
      };
    });
  } finally {
    db.close();
  }
}

function loadFolderRecords() {
  try {
    const parsed = JSON.parse(localStorage.getItem(FOLDER_RECORDS_KEY) || '[]');
    return Array.isArray(parsed) ? parsed.filter(function (item) {
      return item && item.id && item.name;
    }) : [];
  } catch (e) {
    return [];
  }
}

function saveFolderRecords(records) {
  localStorage.setItem(FOLDER_RECORDS_KEY, JSON.stringify(records));
}

function publicFolderRecord(record) {
  return {
    id: record.id,
    path: record.id,
    name: record.name,
    source: 'browser'
  };
}

function normalizeConfiguredFolderRecord(record) {
  return {
    id: record.id,
    path: record.path || record.id,
    name: record.name || 'Carpeta',
    videoCount: Number(record.videoCount) || 0,
    imageCount: Number(record.imageCount) || 0,
    source: record.source || 'api'
  };
}

async function fetchConfiguredFolders() {
  const data = await apiJson('api.php?action=folders');
  const folders = Array.isArray(data) ? data : (Array.isArray(data.folders) ? data.folders : []);

  return folders
    .filter(function (item) { return item && item.id && item.name; })
    .map(normalizeConfiguredFolderRecord);
}

async function addConfiguredFolderPath(folderPath) {
  const form = new FormData();
  form.append('path', folderPath);

  const data = await apiJson('api.php?action=folder_add', {
    method: 'POST',
    body: form
  });

  if (data.error) throw new Error(data.error);
  if (!data.folder) throw new Error('PHP no devolvió la carpeta agregada.');

  return Object.assign({}, data, {
    folder: normalizeConfiguredFolderRecord(data.folder)
  });
}

async function resetConfiguredFolders() {
  const data = await apiJson('api.php?action=folders_reset', {
    method: 'POST'
  });

  if (data.error) throw new Error(data.error);
  return data;
}

async function syncFolderRecords(options) {
  const settings = options || {};
  const records = loadFolderRecords();
  const synced = [];
  let changed = false;
  const removed = [];
  const renamed = [];

  for (const record of records) {
    let handle = null;

    try {
      handle = await getFolderHandle(record.id);
    } catch (e) {}

    if (!handle) {
      changed = true;
      removed.push(record.name);
      continue;
    }

    let hasPermission = false;

    try {
      hasPermission = await ensureFolderPermission(handle, 'read');
    } catch (e) {
      hasPermission = false;
    }

    if (!hasPermission) {
      changed = true;
      removed.push(record.name);
      continue;
    }

    const nextName = handle.name || record.name;
    const nextRecord = Object.assign({}, record, {
      name: nextName
    });

    if (nextName !== record.name) {
      changed = true;
      renamed.push(nextName);
    }

    synced.push(nextRecord);
  }

  synced.sort(function (a, b) {
    return a.name.localeCompare(b.name, 'es', { sensitivity: 'base' });
  });

  if (changed) {
    saveFolderRecords(synced);
  }

  if (settings.notice && removed.length) {
    showAutoNotice('Se quitó una carpeta que ya no está disponible.', 'warning');
  } else if (settings.notice && renamed.length) {
    showAutoNotice('Nombre de carpeta actualizado.', 'info');
  }

  return synced;
}

async function ensureFolderPermission(handle, mode) {
  mode = mode || 'read';

  if (!handle) return false;

  if (typeof handle.queryPermission === 'function') {
    const current = await handle.queryPermission({ mode: mode });
    if (current === 'granted') return true;
  }

  if (typeof handle.requestPermission === 'function') {
    const requested = await handle.requestPermission({ mode: mode });
    return requested === 'granted';
  }

  return true;
}

async function findExistingFolderRecord(newHandle, records) {
  if (!newHandle || typeof newHandle.isSameEntry !== 'function') return null;

  for (const record of records) {
    const existingHandle = await getFolderHandle(record.id);
    if (!existingHandle) continue;

    try {
      if (await newHandle.isSameEntry(existingHandle)) {
        return record;
      }
    } catch (e) {}
  }

  return null;
}

async function listBrowserFolderVideos(folderId) {
  const record = foldersCache.find(function (folder) {
    return folder.id === folderId || folder.path === folderId;
  });

  if (!record) throw new Error('La carpeta no está agregada.');

  const handle = await getFolderHandle(record.id);
  if (!handle) throw new Error('No se encontró el permiso guardado para esa carpeta.');

  const hasPermission = await ensureFolderPermission(handle, 'read');
  if (!hasPermission) throw new Error('Chrome no tiene permiso para leer esa carpeta.');

  const videos = [];

  for await (const entry of handle.values()) {
    if (entry.kind !== 'file') continue;
    if (currentMediaType === 'image' ? !isImageFilename(entry.name) : !isVideoFilename(entry.name)) continue;

    const file = await entry.getFile();

    videos.push({
      name: entry.name,
      title: titleFromFilename(entry.name),
      size: file.size,
      folder: record.id,
      folderName: record.name,
      source: 'browser',
      kind: currentMediaType
    });
  }

  videos.sort(function (a, b) {
    return a.title.localeCompare(b.title, 'es', { sensitivity: 'base' });
  });

  return videos;
}

async function listConfiguredFolderVideos(folderId) {
  const videos = await apiJson(
    'api.php?action=media'
    + '&folder=' + encodeURIComponent(folderId)
    + '&type=' + encodeURIComponent(currentMediaType)
  );

  if (!Array.isArray(videos)) {
    throw new Error('PHP no devolvió la lista de archivos.');
  }

  return videos.map(function (item) {
    return Object.assign({ source: 'api' }, item);
  });
}

async function listFolderVideos(folderId) {
  const record = foldersCache.find(function (folder) {
    return folder.id === folderId || folder.path === folderId;
  });

  if (!record) throw new Error('La carpeta no está agregada.');

  if (record.source === 'browser' || String(record.id).startsWith('fs_')) {
    return listBrowserFolderVideos(folderId);
  }

  return listConfiguredFolderVideos(record.path || record.id);
}

async function getBrowserFileObjectUrl(item) {
  const handle = await getFolderHandle(item.folder);
  if (!handle) throw new Error('No se encontró la carpeta agregada.');

  const hasPermission = await ensureFolderPermission(handle, 'read');
  if (!hasPermission) throw new Error('Chrome no tiene permiso para leer esa carpeta.');

  const fileHandle = await handle.getFileHandle(item.name);
  const file = await fileHandle.getFile();

  return URL.createObjectURL(file);
}

async function resetConfiguredFoldersFromUrl() {
  const params = new URLSearchParams(window.location.search);

  if (params.get('resetFolders') !== '1') return false;

  try {
    await resetConfiguredFolders();
    localStorage.removeItem(FOLDER_RECORDS_KEY);
    localStorage.removeItem(FOLDER_PREF_KEY);
    await clearFolderHandles();
  } catch (e) {
    console.warn('[Carpetas] No se pudo limpiar todo:', e.message);
  }

  currentFolder = '';
  library = [];
  foldersCache = [];
  folderSelect.innerHTML = '<option value="">-- Selecciona una carpeta --</option>';
  renderDownloadFolderOptions();
  renderLibrary();
  statusText.textContent = 'Carpetas agregadas eliminadas del sistema.';
  showAutoNotice('Carpetas agregadas eliminadas.', 'success');

  window.history.replaceState({}, document.title, window.location.pathname);
  return true;
}

// ─── Descarga (no tocar) ──────────────────────────────────────────────────────

function setDownloadMessage(message, type) {
  if (!downloadStatus) return;
  if (downloadStatusTimer) {
    clearTimeout(downloadStatusTimer);
    downloadStatusTimer = null;
  }
  downloadStatus.textContent = message || '';
  downloadStatus.className = 'download-status';
  if (type) downloadStatus.classList.add(type);
}

function setTemporaryDownloadMessage(message, type, durationMs) {
  setDownloadMessage(message, type);

  downloadStatusTimer = setTimeout(function () {
    downloadStatusTimer = null;
    setDownloadMessage('Pega el enlace del video y selecciona la carpeta donde se guardará.', 'info');
  }, durationMs || 4200);
}

function writeDownloadProgress(value) {
  const target = Math.max(0, Math.min(100, Number(value) || 0));

  if (!downloadProgressBar || !downloadPercent) return;

  if (downloadProgressWrap) {
    // Verde fijo y saturado desde el inicio: se aprecia sobre la paleta clara
    // (antes iba de ámbar a verde y el ámbar casi no se veía).
    downloadProgressWrap.style.setProperty('--download-progress-start', 'hsl(146 64% 46%)');
    downloadProgressWrap.style.setProperty('--download-progress-end', 'hsl(152 66% 39%)');
    downloadProgressWrap.style.setProperty('--download-progress-shadow', 'hsla(152 66% 39% / 0.34)');
  }

  downloadProgressValue = target;
  downloadProgressBar.value = target;
  downloadPercent.textContent = Math.round(target) + '%';
}

function stopDownloadLiveProgress() {
  if (downloadLiveProgressFrame) {
    cancelAnimationFrame(downloadLiveProgressFrame);
    downloadLiveProgressFrame = null;
  }

  downloadLiveProgressLastTick = 0;
  downloadLiveProgressCap = 0;
}

function startDownloadLiveProgress(itemCount) {
  if (!downloadProgressWrap) return;

  downloadProgressWrap.classList.add('is-active');

  const count = Math.max(1, Number(itemCount) || 1);
  const headroom = count > 1 ? Math.max(1.2, 14 / count) : 12;
  const nextCap = Math.min(97.5, downloadServerProgressValue + headroom);

  downloadLiveProgressCap = Math.max(downloadLiveProgressCap, nextCap);

  if (downloadLiveProgressFrame) return;
  downloadLiveProgressLastTick = 0;

  function tick(now) {
    if (!activeDownloadJob || downloadProgressWrap.classList.contains('hidden')) {
      stopDownloadLiveProgress();
      downloadProgressWrap.classList.remove('is-active');
      return;
    }

    if (!downloadLiveProgressLastTick) downloadLiveProgressLastTick = now;

    const delta = Math.min(80, now - downloadLiveProgressLastTick);
    downloadLiveProgressLastTick = now;

    if (downloadProgressValue < downloadLiveProgressCap) {
      const rate = count > 1 ? 0.006 : 0.034;
      writeDownloadProgress(Math.min(downloadLiveProgressCap, downloadProgressValue + (delta * rate)));
    }

    downloadLiveProgressFrame = requestAnimationFrame(tick);
  }

  downloadLiveProgressFrame = requestAnimationFrame(tick);
}

function setDownloadProgress(value, immediate, itemCount) {
  const target = Math.max(0, Math.min(100, Number(value) || 0));

  if (!downloadProgressBar || !downloadPercent) return;

  if (downloadProgressFrame) {
    cancelAnimationFrame(downloadProgressFrame);
    downloadProgressFrame = null;
  }

  if (immediate) {
    downloadServerProgressValue = target;
    downloadLiveProgressCap = target;
    writeDownloadProgress(target);
    return;
  }

  if (target < 100 && target <= downloadServerProgressValue) {
    startDownloadLiveProgress(itemCount);
    return;
  }

  downloadServerProgressValue = target;
  downloadLiveProgressCap = Math.max(downloadLiveProgressCap, target);

  const start = downloadProgressValue || Number(downloadProgressBar.value) || 0;

  if (target < 100 && target <= start) {
    startDownloadLiveProgress(itemCount);
    return;
  }

  const startTime = performance.now();
  const duration = target >= 100 ? 180 : 220;

  function tick(now) {
    const elapsed = Math.min(1, (now - startTime) / duration);
    const eased = 1 - Math.pow(1 - elapsed, 3);
    const next = start + (target - start) * eased;

    writeDownloadProgress(next);

    if (elapsed < 1) {
      downloadProgressFrame = requestAnimationFrame(tick);
    } else {
      writeDownloadProgress(target);
      downloadProgressFrame = null;
      if (activeDownloadJob && target < 100) startDownloadLiveProgress(itemCount);
    }
  }

  downloadProgressFrame = requestAnimationFrame(tick);
}

function resetDownloadProgress() {
  if (!downloadProgressWrap) return;
  if (downloadProgressFrame) {
    cancelAnimationFrame(downloadProgressFrame);
    downloadProgressFrame = null;
  }
  stopDownloadLiveProgress();
  downloadProgressWrap.classList.add('hidden');
  downloadProgressWrap.classList.remove('is-active');
  setDownloadProgress(0, true);
  if (downloadProgressLabel) downloadProgressLabel.textContent = 'Descargando';
  if (downloadProgressDetail) downloadProgressDetail.textContent = '';
  downloadLogTail.textContent = '';
}

function normalize(str) {
  return str
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function fuzzyMatch(title, query) {
  const haystack = normalize(title);
  const words = normalize(query).split(' ').filter(Boolean);
  return words.every(function (w) { return haystack.includes(w); });
}

// ─── Grid / Librería ──────────────────────────────────────────────────────────

function clearGrid(message, isEmpty) {
  if (isEmpty === undefined) isEmpty = true;
  grid.innerHTML = '';
  grid.classList.toggle('empty-state', isEmpty);
  if (message) {
    const n = document.createElement('div');
    n.className = 'notice';
    n.textContent = message;
    grid.appendChild(n);
  }
}

function renderLibrary() {
  updateMediaTypeUi();
  const query = searchInput.value.trim();
  const filtered = query
    ? library.filter(function (i) { return fuzzyMatch(i.title, query); })
    : library;

  videoCount.textContent = String(filtered.length);

  if (!library.length) {
    setIdleStatus();
    grid.innerHTML = '<div class="empty-copy"><h3>Tu repertorio aparecerá aquí</h3><p>Elige una carpeta arriba para ver los archivos disponibles.</p></div>';
    grid.classList.add('empty-state');
    return;
  }

  if (!filtered.length) {
    setIdleStatus('No hay coincidencias con la busqueda.');
    clearGrid('No hay coincidencias con la búsqueda.', true);
    return;
  }

  setIdleStatus();
  grid.classList.remove('empty-state');

  const frag = document.createDocumentFragment();

  for (const item of filtered) {
    const card = template.content.firstElementChild.cloneNode(true);
    const kind = item.kind || currentMediaType;
    const icon = card.querySelector('.video-icon');
    card.querySelector('.video-title').textContent = item.title;
    card.querySelector('.video-chip').textContent = prettySize(item.size);
    card.classList.toggle('image-card', kind === 'image');
    if (icon) {
      icon.classList.remove('image-placeholder');
      icon.textContent = kind === 'image' ? '' : PLAY_ICON;
    }

    if (icon && kind === 'image') {
      icon.textContent = '';
      icon.innerHTML = '';
      icon.classList.remove('image-placeholder');
      if (item.source !== 'browser') {
        const thumb = document.createElement('img');
        thumb.alt = '';
        thumb.loading = 'lazy';
        thumb.decoding = 'async';
        thumb.src = buildMediaUrl(item);
        icon.appendChild(thumb);
      } else {
        icon.classList.add('image-placeholder');
      }
    }

    // Identidad del item en el dataset; el click se maneja por delegacion en
    // #grid (un solo listener) en vez de uno por tarjeta.
    card.dataset.folder = item.folder == null ? '' : String(item.folder);
    card.dataset.name = item.name == null ? '' : String(item.name);

    frag.appendChild(card);
  }

  grid.innerHTML = '';
  grid.appendChild(frag);
}

function librarySignature(items) {
  return (items || []).map(function (item) {
    return [
      item.kind || currentMediaType,
      item.folder,
      item.name,
      item.size || 0,
      item.folderName || ''
    ].join('|');
  }).join('\n');
}

async function loadVideos(folder, options) {
  const settings = options || {};
  const labels = mediaLabels();

  if (!folder) {
    library = [];
    renderLibrary();
    return;
  }

  if (folder === TEMP_PLAYLIST_ID) {
    currentMediaType = 'video';
    if (mediaTypeSelect) mediaTypeSelect.value = 'video';
    library = tempPlaylistItems.slice();
    renderLibrary();
    return;
  }

  if (folder !== '__all__' && !settings.skipFolderRefresh) {
    await loadFolders(folder, { notice: true, skipAutoLoad: true });

    if (!foldersCache.some(function (item) { return item.id === folder || item.path === folder; })) {
      currentFolder = '';
      localStorage.removeItem(FOLDER_PREF_KEY);
      folderSelect.value = '';
      renderDownloadFolderOptions();
      library = [];
      renderLibrary();
      statusText.textContent = 'La carpeta seleccionada ya no está disponible.';
      clearGrid('Agrega nuevamente la carpeta si todavía existe.', true);
      return;
    }
  }

  statusText.textContent = 'Cargando ' + labels.plural + '...';
  if (!settings.silent) {
    clearGrid('Cargando...', true);
  }

  try {
    const previousSignature = librarySignature(library);
    let nextLibrary = [];

    if (folder === '__all__') {
      const groups = await Promise.all(foldersCache.map(function (item) {
        return listFolderVideos(item.path || item.id).catch(function () {
          return [];
        });
      }));

      nextLibrary = groups.flat();
      nextLibrary.sort(function (a, b) {
        return a.title.localeCompare(b.title, 'es', { sensitivity: 'base' });
      });
    } else {
      nextLibrary = await listFolderVideos(folder);
    }

    const nextSignature = librarySignature(nextLibrary);

    if (settings.silent && nextSignature === previousSignature) {
      setIdleStatus();
      videoCount.textContent = String(
        searchInput.value.trim()
          ? library.filter(function (i) { return fuzzyMatch(i.title, searchInput.value.trim()); }).length
          : library.length
      );
      return;
    }

    library = nextLibrary;
    renderLibrary();
  } catch (e) {
    statusText.textContent = e.message || 'No se pudieron cargar los archivos.';
    clearGrid('No se pudieron cargar los archivos.', true);
    library = [];
  }
}

function renderDownloadFolderOptions() {
  if (!downloadFolderSelect) return;

  const selected = downloadFolderSelect.value;
  const frag = document.createDocumentFragment();

  const def = document.createElement('option');
  def.value = '';
  def.textContent = '-- Selecciona una carpeta --';
  frag.appendChild(def);

  for (const f of foldersCache) {
    const o = document.createElement('option');
    o.value = f.path;
    o.textContent = f.name;
    frag.appendChild(o);
  }

  downloadFolderSelect.innerHTML = '';
  downloadFolderSelect.appendChild(frag);

  if (selected && foldersCache.some(function (f) { return f.path === selected; })) {
    downloadFolderSelect.value = selected;
  } else if (currentFolder && currentFolder !== '__all__' && foldersCache.some(function (f) { return f.path === currentFolder; })) {
    downloadFolderSelect.value = currentFolder;
  } else {
    downloadFolderSelect.value = '';
  }

  updateDownloadStartButtonState();
}

function ensureTempPlaylistOption() {
  if (!folderSelect) return;

  const existing = folderSelect.querySelector('option[value="' + TEMP_PLAYLIST_ID + '"]');
  if (existing) existing.remove();
}

async function loadFolders(preferredFolder, options) {
  const settings = options || {};

  try {
    const previousNames = new Map(foldersCache.map(function (folder) {
      return [folder.id, folder.name];
    }));

    foldersCache = await fetchConfiguredFolders();

    if (settings.notice && previousNames.size) {
      const currentIds = new Set(foldersCache.map(function (folder) { return folder.id; }));
      const removed = Array.from(previousNames.keys()).filter(function (id) { return !currentIds.has(id); });
      const renamed = foldersCache.some(function (folder) {
        return previousNames.has(folder.id) && previousNames.get(folder.id) !== folder.name;
      });

      if (removed.length) {
        showAutoNotice('Se quitó una carpeta que ya no está disponible.', 'warning');
      } else if (renamed) {
        showAutoNotice('Nombre de carpeta actualizado.', 'info');
      }
    }

    const frag = document.createDocumentFragment();

    const def = document.createElement('option');
    def.value = '';
    def.textContent = '-- Selecciona una carpeta --';
    def.selected = true;
    frag.appendChild(def);

    if (foldersCache.length) {
      const all = document.createElement('option');
      all.value = '__all__';
      all.textContent = 'Todos';
      frag.appendChild(all);
    }

    for (const f of foldersCache) {
      const o = document.createElement('option');
      o.value = f.path;
      o.textContent = f.name;
      frag.appendChild(o);
    }

    folderSelect.innerHTML = '';
    folderSelect.appendChild(frag);

    renderDownloadFolderOptions();

    const savedFolder = localStorage.getItem(FOLDER_PREF_KEY);
    const desiredFolder = preferredFolder || savedFolder;

  if (!foldersCache.length) {
    currentFolder = '';
    localStorage.removeItem(FOLDER_PREF_KEY);
    renderLibrary();
    statusText.textContent = 'Agrega una carpeta con archivos para comenzar.';
    clearGrid('No hay carpetas agregadas todavía.', true);
    return;
  }

    if (desiredFolder) {
      folderSelect.value = desiredFolder;
      if (folderSelect.value === desiredFolder) {
        currentFolder = desiredFolder;
        if (!settings.skipAutoLoad) {
          loadVideos(desiredFolder);
        }
      } else {
        if (currentFolder === desiredFolder) currentFolder = '';
        folderSelect.value = '';
        localStorage.removeItem(FOLDER_PREF_KEY);
        renderDownloadFolderOptions();
        library = [];
        renderLibrary();
        statusText.textContent = 'La carpeta seleccionada ya no está disponible.';
      }
    }
  } catch (e) {
    statusText.textContent = 'Error al cargar las carpetas.';
    folderSelect.innerHTML = '<option value="">Error al cargar</option>';
    console.warn('[Carpetas] Error al cargar:', e.message);
  }
}

function setAddFolderBusy(isBusy) {
  if (!addFolderBtn) return;

  addFolderBtn.disabled = isBusy;
  addFolderBtn.classList.toggle('busy', isBusy);
}

function openFolderAccessModal() {
  if (!folderAccessModal) return Promise.resolve('');

  return new Promise(function (resolve) {
    folderAccessResolve = resolve;
    if (folderPathInput) {
      folderPathInput.value = '';
      folderPathInput.classList.remove('field-error');
    }

    folderAccessModal.classList.add('active');
    folderAccessModal.setAttribute('aria-hidden', 'false');
    lockUI();

    setTimeout(function () {
      if (folderPathInput) {
        folderPathInput.focus();
      } else if (folderAccessChooseBtn) {
        folderAccessChooseBtn.focus();
      }
    }, 80);
  });
}

function closeFolderAccessModal(value) {
  if (!folderAccessModal) return;

  folderAccessModal.classList.remove('active');
  folderAccessModal.setAttribute('aria-hidden', 'true');
  unlockUI();

  if (folderAccessResolve) {
    const resolve = folderAccessResolve;
    folderAccessResolve = null;
    resolve(value || '');
  }
}

function showAutoNotice(message, type, durationMs) {
  if (!noticeToast || !noticeToastText) return;

  const kind = type || 'info';
  const icons = {
    info: 'i',
    success: '✓',
    warning: '!',
    error: '×'
  };

  if (noticeToastTimer) {
    clearTimeout(noticeToastTimer);
    noticeToastTimer = null;
  }

  noticeToastText.textContent = message || '';
  if (noticeToastIcon) noticeToastIcon.textContent = icons[kind] || icons.info;

  noticeToast.className = 'notice-toast active ' + kind;
  noticeToast.setAttribute('aria-hidden', 'false');

  noticeToastTimer = setTimeout(function () {
    noticeToast.classList.remove('active');
    noticeToast.setAttribute('aria-hidden', 'true');
  }, durationMs || 2800);
}

async function startAddFolder() {
  if (!addFolderBtn || addFolderBtn.disabled) return;

  try {
    const folderPath = await openFolderAccessModal();

    if (!folderPath) {
      statusText.textContent = 'No se agregó ninguna carpeta.';
      return;
    }

    setAddFolderBusy(true);
    statusText.textContent = 'Agregando carpeta...';

    const result = await addConfiguredFolderPath(folderPath);
    const selectedFolder = result.folder;

    if (result.alreadyAdded) {
      await loadFolders(selectedFolder.path, { skipAutoLoad: true });
      folderSelect.value = selectedFolder.path;
      currentFolder = selectedFolder.path;
      localStorage.setItem(FOLDER_PREF_KEY, currentFolder);
      await loadVideos(currentFolder);
      statusText.textContent = 'Esa carpeta ya está agregada.';
      showAutoNotice('Esa carpeta ya está agregada.', 'warning');
      return;
    }

    await loadFolders(selectedFolder.path, { skipAutoLoad: true });
    folderSelect.value = selectedFolder.path;
    currentFolder = selectedFolder.path;
    localStorage.setItem(FOLDER_PREF_KEY, currentFolder);
    await loadVideos(currentFolder);

    if (library.length) {
      statusText.textContent = 'Carpeta agregada: ' + selectedFolder.name;
      showAutoNotice('Carpeta agregada: ' + selectedFolder.name, 'success');
    } else {
      const labels = mediaLabels();
      statusText.textContent = 'Carpeta agregada. 0 ' + labels.plural + ' compatibles encontrados.';
      showAutoNotice('Carpeta agregada. 0 ' + labels.plural + ' compatibles encontrados.', 'info');
    }
  } catch (e) {
    if (e && e.name === 'AbortError') {
      statusText.textContent = 'No se agregó ninguna carpeta.';
    } else {
      statusText.textContent = e.message || 'No se pudo agregar la carpeta.';
    }
  } finally {
    setAddFolderBusy(false);
  }
}

// ─── UI helpers ───────────────────────────────────────────────────────────────

function setUtilityStatus(el, message, type) {
  if (!el) return;
  el.textContent = message || '';
  el.className = 'utility-status';
  if (type) el.classList.add(type);
}

function apiRenameMedia(folder, file, type, newName) {
  const form = new FormData();
  form.append('folder', folder);
  form.append('file', file);
  form.append('type', type);
  form.append('newName', newName);

  return apiJson('api.php?action=rename_media', {
    method: 'POST',
    body: form
  });
}

async function apiFetchPlaylists() {
  const data = await apiJson('api.php?action=playlists');
  if (data.error) throw new Error(data.error);
  return Array.isArray(data.playlists) ? data.playlists : [];
}

async function apiSavePlaylist(name, items) {
  const form = new FormData();
  form.append('name', name);
  form.append('items', JSON.stringify(items || []));

  const data = await apiJson('api.php?action=playlist_save', {
    method: 'POST',
    body: form
  });

  if (data.error) throw new Error(data.error);
  return data;
}

async function apiDeletePlaylist(id) {
  const form = new FormData();
  form.append('id', id);

  const data = await apiJson('api.php?action=playlist_delete', {
    method: 'POST',
    body: form
  });

  if (data.error) throw new Error(data.error);
  return data;
}

async function apiRemovePlaylistItem(id, index) {
  const form = new FormData();
  form.append('id', id);
  form.append('index', String(index));

  const data = await apiJson('api.php?action=playlist_remove_item', {
    method: 'POST',
    body: form
  });

  if (data.error) throw new Error(data.error);
  return data;
}

function formatPlaylistDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';

  return date.toLocaleDateString('es-CR', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  });
}

function fillApiFolderSelect(selectEl, includeAll) {
  if (!selectEl) return;

  const selected = selectEl.value;
  const frag = document.createDocumentFragment();

  if (includeAll) {
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'Todas las carpetas';
    frag.appendChild(all);
  }

  foldersCache
    .filter(function (folder) { return folder.source !== 'browser'; })
    .forEach(function (folder) {
      const option = document.createElement('option');
      option.value = folder.path || folder.id;
      option.textContent = folder.name;
      frag.appendChild(option);
    });

  selectEl.innerHTML = '';
  selectEl.appendChild(frag);

  if (selected && Array.from(selectEl.options).some(function (option) { return option.value === selected; })) {
    selectEl.value = selected;
  }
}

function syncRenameInputFromSelection() {
  if (!renameFileSelect || !renameNameInput) return;

  const item = renameMediaItems[Number(renameFileSelect.value) || 0];

  if (!item) {
    renameNameInput.value = '';
    if (renameExtensionLabel) renameExtensionLabel.textContent = '';
    return;
  }

  const extMatch = String(item.name || '').match(/(\.[^.]+)$/);
  renameNameInput.value = titleFromFilename(item.name);
  if (renameExtensionLabel) renameExtensionLabel.textContent = extMatch ? extMatch[1] : '';
}

function updateRenameFilePickerLabel() {
  if (!renameFilePickerLabel) return;

  const item = renameMediaItems[Number(renameFileSelect && renameFileSelect.value) || 0];

  if (!renameMediaItems.length || !item) {
    renameFilePickerLabel.textContent = 'Elige un archivo…';
    renameFilePickerLabel.classList.add('is-placeholder');
    return;
  }

  renameFilePickerLabel.textContent = item.title || titleFromFilename(item.name);
  renameFilePickerLabel.classList.remove('is-placeholder');
}

function renderRenameFilePickerList(filter) {
  if (!renameFilePickerList) return;

  const q = String(filter || '').trim().toLowerCase();
  const selected = Number(renameFileSelect && renameFileSelect.value) || 0;
  const frag = document.createDocumentFragment();
  let shown = 0;

  const showFolderNames = !!(renameFolderSelect && renameFolderSelect.value === '__all__');

  renameMediaItems.forEach(function (item, index) {
    const title = item.title || titleFromFilename(item.name);
    let name = item.name || '';
    if (showFolderNames && item.folderName) name += '  ·  ' + item.folderName;

    if (q && (title + ' ' + name).toLowerCase().indexOf(q) === -1) return;

    const row = document.createElement('button');
    row.type = 'button';
    row.className = 'file-picker-row' + (index === selected ? ' is-selected' : '');
    row.dataset.index = String(index);
    row.setAttribute('role', 'option');

    const t = document.createElement('strong');
    t.textContent = title;
    const n = document.createElement('span');
    n.textContent = name;

    row.appendChild(t);
    row.appendChild(n);
    frag.appendChild(row);
    shown++;
  });

  renameFilePickerList.innerHTML = '';
  renameFilePickerList.appendChild(frag);
  if (renameFilePickerEmpty) renameFilePickerEmpty.hidden = shown !== 0;
}

function selectRenameFile(index) {
  if (!renameFileSelect) return;

  const item = renameMediaItems[index];
  if (!item) return;

  renameFileSelect.value = String(index);
  syncRenameInputFromSelection();
  updateRenameFilePickerLabel();
  closeRenameFilePicker();
  setUtilityStatus(renameStatus, 'Listo para cambiar el nombre.');

  setTimeout(function () {
    if (renameNameInput) {
      renameNameInput.focus();
      renameNameInput.select();
    }
  }, 60);
}

function openRenameFilePicker() {
  if (!renameFilePickerModal) return;

  if (!renameMediaItems.length) {
    setUtilityStatus(renameStatus, 'No hay archivos para elegir.', 'error');
    return;
  }

  if (renameFilePickerSearch) renameFilePickerSearch.value = '';
  renderRenameFilePickerList('');

  renameFilePickerModal.classList.add('active');
  renameFilePickerModal.setAttribute('aria-hidden', 'false');

  setTimeout(function () {
    if (renameFilePickerSearch) renameFilePickerSearch.focus();
    const sel = renameFilePickerList && renameFilePickerList.querySelector('.file-picker-row.is-selected');
    if (sel && sel.scrollIntoView) sel.scrollIntoView({ block: 'nearest' });
  }, 50);
}

function closeRenameFilePicker() {
  if (!renameFilePickerModal) return;
  renameFilePickerModal.classList.remove('active');
  renameFilePickerModal.setAttribute('aria-hidden', 'true');
}

async function refreshRenameFileList() {
  if (!renameFolderSelect || !renameTypeSelect || !renameFileSelect) return;

  const folder = renameFolderSelect.value;
  const type = renameTypeSelect.value === 'image' ? 'image' : 'video';

  renameMediaItems = [];
  renameFileSelect.innerHTML = '';
  if (renameNameInput) renameNameInput.value = '';
  if (renameExtensionLabel) renameExtensionLabel.textContent = '';
  updateRenameFilePickerLabel();

  if (!folder) {
    setUtilityStatus(renameStatus, 'Selecciona una carpeta.', 'error');
    return;
  }

  setUtilityStatus(renameStatus, 'Cargando archivos...');

  try {
    let items = [];

    if (folder === '__all__') {
      const groups = await Promise.all(
        foldersCache
          .filter(function (f) { return f.source !== 'browser'; })
          .map(function (f) {
            return apiJson(
              'api.php?action=media'
              + '&folder=' + encodeURIComponent(f.path || f.id)
              + '&type=' + encodeURIComponent(type)
            ).catch(function () { return []; });
          })
      );
      items = groups.flat().filter(function (item) { return item && item.name; });
      items.sort(function (a, b) {
        return String(a.title || a.name).localeCompare(String(b.title || b.name), 'es', { sensitivity: 'base' });
      });
    } else {
      items = await apiJson(
        'api.php?action=media'
        + '&folder=' + encodeURIComponent(folder)
        + '&type=' + encodeURIComponent(type)
      );
    }

    if (!Array.isArray(items)) throw new Error('PHP no devolvio la lista de archivos.');

    renameMediaItems = items.map(function (item) {
      return Object.assign({ source: 'api', kind: type }, item);
    });

    const frag = document.createDocumentFragment();

    renameMediaItems.forEach(function (item, index) {
      const option = document.createElement('option');
      option.value = String(index);
      option.textContent = (item.title || titleFromFilename(item.name)) + ' - ' + (item.name || '');
      frag.appendChild(option);
    });

    renameFileSelect.appendChild(frag);

    if (!renameMediaItems.length) {
      setUtilityStatus(renameStatus, 'No hay archivos de ese tipo en la carpeta.', 'error');
      return;
    }

    syncRenameInputFromSelection();
    updateRenameFilePickerLabel();
    if (renameFilePickerModal && renameFilePickerModal.classList.contains('active')) {
      renderRenameFilePickerList(renameFilePickerSearch ? renameFilePickerSearch.value : '');
    }
    setUtilityStatus(renameStatus, 'Listo para cambiar el nombre.');
  } catch (e) {
    setUtilityStatus(renameStatus, e.message || 'No se pudieron cargar los archivos.', 'error');
  }
}

async function openRenameModal() {
  if (!renameModal) return;

  if (!foldersCache.length) {
    await loadFolders(currentFolder, { skipAutoLoad: true });
  }

  fillApiFolderSelect(renameFolderSelect, false);

  if (!renameFolderSelect || !renameFolderSelect.options.length) {
    showAutoNotice('Agrega una carpeta por ruta para poder renombrar archivos.', 'warning');
    return;
  }

  const allOption = document.createElement('option');
  allOption.value = '__all__';
  allOption.textContent = 'Todos';
  renameFolderSelect.insertBefore(allOption, renameFolderSelect.firstChild);

  if (currentFolder === '__all__') {
    renameFolderSelect.value = '__all__';
  } else if (currentFolder && currentFolder !== TEMP_PLAYLIST_ID) {
    renameFolderSelect.value = currentFolder;
  }

  if (renameTypeSelect) renameTypeSelect.value = currentMediaType === 'image' ? 'image' : 'video';
  setUtilityStatus(renameStatus, '');

  renameModal.classList.add('active');
  renameModal.setAttribute('aria-hidden', 'false');
  lockUI();

  await refreshRenameFileList();

  setTimeout(function () {
    if (renameNameInput) {
      renameNameInput.focus();
      renameNameInput.select();
    }
  }, 80);
}

function closeRenameModal() {
  if (!renameModal) return;
  renameModal.classList.remove('active');
  renameModal.setAttribute('aria-hidden', 'true');
  unlockUI();
}

async function saveRenameMedia() {
  if (!renameFolderSelect || !renameFileSelect || !renameNameInput || !renameTypeSelect || !renameSaveBtn) return;

  const item = renameMediaItems[Number(renameFileSelect.value) || 0];
  if (!item) {
    setUtilityStatus(renameStatus, 'Selecciona un archivo.', 'error');
    return;
  }

  // Con "Todos" seleccionado, el archivo lleva su carpeta real.
  const folder = renameFolderSelect.value === '__all__'
    ? (item.folder || '')
    : renameFolderSelect.value;
  const type = renameTypeSelect.value === 'image' ? 'image' : 'video';
  const newName = renameNameInput.value.trim();

  if (!newName) {
    setUtilityStatus(renameStatus, 'Escribe el nuevo nombre.', 'error');
    renameNameInput.focus();
    return;
  }

  if (newName.length > 120) {
    setUtilityStatus(renameStatus, 'El nombre es demasiado largo (máximo 120 caracteres).', 'error');
    renameNameInput.focus();
    return;
  }

  renameSaveBtn.disabled = true;
  setUtilityStatus(renameStatus, 'Cambiando nombre...');

  try {
    const result = await apiRenameMedia(folder, item.name, type, newName);
    if (result.error) throw new Error(result.error);

    const renamedItem = result.item || null;

    tempPlaylistItems = tempPlaylistItems.map(function (playlistItem) {
      if (playlistItem.folder === folder && playlistItem.name === item.name && renamedItem) {
        return Object.assign({}, playlistItem, renamedItem);
      }

      return playlistItem;
    });

    savedPlaylists = savedPlaylists.map(function (playlist) {
      return Object.assign({}, playlist, {
        items: (playlist.items || []).map(function (playlistItem) {
          if (playlistItem.folder === folder && playlistItem.name === item.name && renamedItem) {
            return Object.assign({}, playlistItem, renamedItem);
          }

          return playlistItem;
        })
      });
    });

    await loadFolders(currentFolder, { skipAutoLoad: true });

    if (currentFolder === TEMP_PLAYLIST_ID) {
      ensureTempPlaylistOption();
      folderSelect.value = TEMP_PLAYLIST_ID;
      await loadVideos(TEMP_PLAYLIST_ID);
    } else if ((currentFolder === folder || currentFolder === '__all__') && currentMediaType === type) {
      await loadVideos(currentFolder, { skipFolderRefresh: true });
    }

    closeRenameModal();
    showAutoNotice('Nombre actualizado.', 'success');
    statusText.textContent = 'Nombre actualizado: ' + (renamedItem ? renamedItem.title : newName);
  } catch (e) {
    setUtilityStatus(renameStatus, e.message || 'No se pudo cambiar el nombre.', 'error');
  } finally {
    renameSaveBtn.disabled = false;
  }
}

function renderTempPlaylistFolderFilter() {
  if (!tempPlaylistFolderSelect) return;

  const selected = tempPlaylistFolderSelect.value;
  const frag = document.createDocumentFragment();
  const all = document.createElement('option');
  all.value = '';
  all.textContent = 'Todas las carpetas';
  frag.appendChild(all);

  const seen = new Map();
  tempPlaylistCandidateItems.forEach(function (item) {
    if (!seen.has(item.folder)) {
      seen.set(item.folder, item.folderName || 'Carpeta');
    }
  });

  Array.from(seen.entries())
    .sort(function (a, b) { return a[1].localeCompare(b[1], 'es', { sensitivity: 'base' }); })
    .forEach(function (entry) {
      const option = document.createElement('option');
      option.value = entry[0];
      option.textContent = entry[1];
      frag.appendChild(option);
    });

  tempPlaylistFolderSelect.innerHTML = '';
  tempPlaylistFolderSelect.appendChild(frag);

  if (selected && seen.has(selected)) {
    tempPlaylistFolderSelect.value = selected;
  }
}

function updateTempPlaylistStatus() {
  const count = tempPlaylistSelectedKeys.size;
  const total = tempPlaylistCandidateItems.length;
  const message = count
    ? count + ' video' + (count === 1 ? '' : 's') + ' seleccionado' + (count === 1 ? '' : 's') + '.'
    : total + ' videos disponibles para elegir.';

  setUtilityStatus(tempPlaylistStatus, message);
}

function resetPlaylistPickerFilters() {
  let changed = false;

  if (tempPlaylistSearchInput && tempPlaylistSearchInput.value) {
    tempPlaylistSearchInput.value = '';
    changed = true;
  }

  if (tempPlaylistFolderSelect && tempPlaylistFolderSelect.value) {
    tempPlaylistFolderSelect.value = '';
    changed = true;
  }

  return changed;
}

function renderTempPlaylistList() {
  if (!tempPlaylistList) return;

  const folder = tempPlaylistFolderSelect ? tempPlaylistFolderSelect.value : '';
  const query = tempPlaylistSearchInput ? tempPlaylistSearchInput.value.trim() : '';
  const filtered = tempPlaylistCandidateItems.filter(function (item) {
    if (folder && item.folder !== folder) return false;
    if (query && !fuzzyMatch((item.title || '') + ' ' + (item.name || '') + ' ' + (item.folderName || ''), query)) return false;
    return true;
  });

  tempPlaylistList.innerHTML = '';

  if (!filtered.length) {
    const empty = document.createElement('div');
    empty.className = 'notice';
    empty.textContent = 'No hay videos con ese filtro.';
    tempPlaylistList.appendChild(empty);
    updateTempPlaylistStatus();
    return;
  }

  const frag = document.createDocumentFragment();

  filtered.forEach(function (item) {
    const key = mediaItemKey(item);
    const row = document.createElement('label');
    row.className = 'playlist-pick';

    const check = document.createElement('input');
    check.type = 'checkbox';
    check.checked = tempPlaylistSelectedKeys.has(key);

    const meta = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = item.title || titleFromFilename(item.name);
    const folderName = document.createElement('span');
    folderName.textContent = item.folderName || 'Carpeta';
    meta.appendChild(title);
    meta.appendChild(folderName);

    const size = document.createElement('span');
    size.textContent = prettySize(item.size);

    check.addEventListener('change', function () {
      if (check.checked) {
        tempPlaylistSelectedKeys.add(key);
      } else {
        tempPlaylistSelectedKeys.delete(key);
      }

      if (resetPlaylistPickerFilters()) {
        setTimeout(renderTempPlaylistList, 0);
      } else {
        updateTempPlaylistStatus();
      }
    });

    row.appendChild(check);
    row.appendChild(meta);
    row.appendChild(size);
    frag.appendChild(row);
  });

  tempPlaylistList.appendChild(frag);
  updateTempPlaylistStatus();
}

async function openTempPlaylistModal() {
  if (!tempPlaylistModal) return;

  tempPlaylistModal.classList.add('active');
  tempPlaylistModal.setAttribute('aria-hidden', 'false');
  lockUI();
  setUtilityStatus(tempPlaylistStatus, 'Cargando videos...');
  if (tempPlaylistList) tempPlaylistList.innerHTML = '';

  try {
    const items = await apiJson('api.php?action=all&type=video');
    if (!Array.isArray(items)) throw new Error('PHP no devolvio la lista de videos.');

    tempPlaylistCandidateItems = items.map(function (item) {
      return Object.assign({ source: 'api', kind: 'video' }, item);
    });

    tempPlaylistSelectedKeys = new Set(tempPlaylistItems.map(mediaItemKey));
    renderTempPlaylistFolderFilter();
    renderTempPlaylistList();

    setTimeout(function () {
      if (tempPlaylistSearchInput) tempPlaylistSearchInput.focus();
    }, 80);
  } catch (e) {
    setUtilityStatus(tempPlaylistStatus, e.message || 'No se pudieron cargar los videos.', 'error');
  }
}

function closeTempPlaylistModal() {
  if (!tempPlaylistModal) return;
  tempPlaylistModal.classList.remove('active');
  tempPlaylistModal.setAttribute('aria-hidden', 'true');
  unlockUI();
}

function openPlaylistNameModal(items) {
  pendingPlaylistItems = (items || []).slice();

  if (!playlistNameModal) return;

  if (playlistNameInput) {
    playlistNameInput.value = '';
  }

  setUtilityStatus(playlistNameStatus, pendingPlaylistItems.length + ' videos seleccionados.');
  playlistNameModal.classList.add('active');
  playlistNameModal.setAttribute('aria-hidden', 'false');
  lockUI();

  setTimeout(function () {
    if (playlistNameInput) playlistNameInput.focus();
  }, 80);
}

function closePlaylistNameModal() {
  if (!playlistNameModal) return;
  playlistNameModal.classList.remove('active');
  playlistNameModal.setAttribute('aria-hidden', 'true');
  pendingPlaylistItems = [];
}

async function saveNamedPlaylist() {
  if (!playlistNameInput || !playlistNameSaveBtn) return;

  const name = playlistNameInput.value.trim();
  if (!name) {
    setUtilityStatus(playlistNameStatus, 'Escribe un nombre para la playlist.', 'error');
    playlistNameInput.focus();
    return;
  }

  playlistNameSaveBtn.disabled = true;
  setUtilityStatus(playlistNameStatus, 'Guardando playlist...');

  try {
    const result = await apiSavePlaylist(name, pendingPlaylistItems);
    savedPlaylists = Array.isArray(result.playlists) ? result.playlists : [];
    closePlaylistNameModal();
    closeTempPlaylistModal();
    tempPlaylistItems = [];
    tempPlaylistSelectedKeys = new Set();
    showAutoNotice('Playlist guardada: ' + (result.playlist ? result.playlist.name : name), 'success');
  } catch (e) {
    setUtilityStatus(playlistNameStatus, e.message || 'No se pudo guardar la playlist.', 'error');
  } finally {
    playlistNameSaveBtn.disabled = false;
  }
}

function clearTempPlaylist() {
  tempPlaylistItems = [];
  tempPlaylistSelectedKeys = new Set();
  ensureTempPlaylistOption();

  if (currentFolder === TEMP_PLAYLIST_ID) {
    currentFolder = '';
    folderSelect.value = '';
    library = [];
    renderLibrary();
  }

  renderTempPlaylistList();
  showAutoNotice('Seleccion limpia.', 'info');
}

async function saveTempPlaylist() {
  const itemByKey = new Map(tempPlaylistCandidateItems.map(function (item) {
    return [mediaItemKey(item), item];
  }));
  const selected = Array.from(tempPlaylistSelectedKeys)
    .map(function (key) { return itemByKey.get(key); })
    .filter(Boolean);

  if (!selected.length) {
    setUtilityStatus(tempPlaylistStatus, 'Selecciona al menos un video.', 'error');
    return;
  }

  const items = selected.map(function (item) {
    return Object.assign({}, item, { source: 'api', kind: 'video' });
  });

  openPlaylistNameModal(items);
}

function closePlaylistsModal() {
  if (!playlistsModal) return;
  playlistsModal.classList.remove('active');
  playlistsModal.setAttribute('aria-hidden', 'true');
  unlockUI();
}

function closePlaylistDetailModal() {
  if (!playlistDetailModal) return;
  playlistDetailModal.classList.remove('active');
  playlistDetailModal.setAttribute('aria-hidden', 'true');
  activePlaylistDetail = null;
  unlockUI();
}

function returnToPlaylistDetail(delayMs) {
  if (!playlistReturnContext || !playlistReturnContext.playlist) return false;

  const context = playlistReturnContext;
  playlistReturnContext = null;

  setTimeout(function () {
    if (context.monitorValue && monitorSelect) {
      monitorSelect.value = context.monitorValue;
      localStorage.setItem(MONITOR_PREF_KEY, monitorSelect.value);
    }

    currentMediaType = 'video';
    if (mediaTypeSelect) {
      mediaTypeSelect.value = 'video';
      localStorage.setItem(FILE_TYPE_PREF_KEY, 'video');
    }

    updateMediaTypeUi();
    openPlaylistDetailModal(context.playlist);
  }, delayMs || 350);

  return true;
}

function populatePlaylistDetailMonitors() {
  if (!playlistDetailMonitorSelect) return;

  const selected = monitorSelect ? monitorSelect.value : '';
  const frag = document.createDocumentFragment();

  monitorTargets.forEach(function (target) {
    const option = document.createElement('option');
    option.value = target.value;
    option.textContent = target.label;
    frag.appendChild(option);
  });

  playlistDetailMonitorSelect.innerHTML = '';
  playlistDetailMonitorSelect.appendChild(frag);

  if (selected && Array.from(playlistDetailMonitorSelect.options).some(function (option) { return option.value === selected; })) {
    playlistDetailMonitorSelect.value = selected;
  }
}

function renderSavedPlaylists() {
  if (!playlistsList) return;

  playlistsList.innerHTML = '';

  if (!savedPlaylists.length) {
    const empty = document.createElement('div');
    empty.className = 'notice';
    empty.textContent = 'No hay playlists guardadas.';
    playlistsList.appendChild(empty);
    setUtilityStatus(playlistsStatus, '');
    return;
  }

  const frag = document.createDocumentFragment();

  savedPlaylists.forEach(function (playlist) {
    const button = document.createElement('div');
    button.className = 'saved-playlist-item';
    button.tabIndex = 0;
    button.setAttribute('role', 'button');

    const meta = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = playlist.name || 'Playlist';
    const date = document.createElement('span');
    date.textContent = formatPlaylistDate(playlist.createdAt) || 'Sin fecha';
    meta.appendChild(title);
    meta.appendChild(date);

    const count = document.createElement('span');
    count.textContent = (playlist.count || (playlist.items || []).length || 0) + ' videos';

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.className = 'playlist-delete-btn';
    deleteBtn.textContent = '×';
    deleteBtn.setAttribute('aria-label', 'Eliminar playlist');
    deleteBtn.title = 'Eliminar playlist';

    button.appendChild(meta);
    button.appendChild(count);
    button.appendChild(deleteBtn);

    button.addEventListener('click', function () {
      closePlaylistsModal();
      openPlaylistDetailModal(playlist);
    });

    button.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        closePlaylistsModal();
        openPlaylistDetailModal(playlist);
      }
    });

    deleteBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      deleteSavedPlaylistFromList(playlist);
    });

    frag.appendChild(button);
  });

  playlistsList.appendChild(frag);
  setUtilityStatus(playlistsStatus, savedPlaylists.length + ' playlist' + (savedPlaylists.length === 1 ? '' : 's') + ' guardada' + (savedPlaylists.length === 1 ? '' : 's') + '.');
}

async function openPlaylistsModal() {
  if (!playlistsModal) return;

  currentMediaType = 'video';
  if (mediaTypeSelect) {
    mediaTypeSelect.value = 'video';
    localStorage.setItem(FILE_TYPE_PREF_KEY, 'video');
  }
  updateMediaTypeUi();

  playlistsModal.classList.add('active');
  playlistsModal.setAttribute('aria-hidden', 'false');
  lockUI();

  if (playlistsList) playlistsList.innerHTML = '';
  setUtilityStatus(playlistsStatus, 'Cargando playlists...');

  try {
    savedPlaylists = await apiFetchPlaylists();
    renderSavedPlaylists();
  } catch (e) {
    setUtilityStatus(playlistsStatus, e.message || 'No se pudieron cargar las playlists.', 'error');
  }
}

function openPlaylistDetailModal(playlist) {
  if (!playlistDetailModal || !playlist) return;

  activePlaylistDetail = playlist;
  populatePlaylistDetailMonitors();

  if (playlistDetailTitle) playlistDetailTitle.textContent = playlist.name || 'Playlist';
  if (playlistDetailMeta) {
    playlistDetailMeta.textContent = (playlist.items || []).length + ' videos · ' + (formatPlaylistDate(playlist.createdAt) || 'Sin fecha');
  }

  if (playlistDetailList) {
    playlistDetailList.innerHTML = '';

    const items = playlist.items || [];

    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'notice';
      empty.textContent = 'Esta playlist no tiene videos disponibles.';
      playlistDetailList.appendChild(empty);
    } else {
      const frag = document.createDocumentFragment();

      items.forEach(function (item, index) {
        const row = document.createElement('div');
        row.className = 'playlist-detail-item';
        row.tabIndex = 0;
        row.setAttribute('role', 'button');

        const meta = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = item.title || titleFromFilename(item.name);
        const folderName = document.createElement('span');
        folderName.textContent = item.folderName || 'Carpeta';
        meta.appendChild(title);
        meta.appendChild(folderName);

        const order = document.createElement('span');
        order.textContent = String(index + 1).padStart(2, '0');

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'playlist-delete-btn playlist-item-remove-btn';
        removeBtn.setAttribute('aria-label', 'Quitar video de la playlist');
        removeBtn.title = 'Quitar video';

        row.appendChild(meta);
        row.appendChild(order);
        row.appendChild(removeBtn);
        row.addEventListener('click', function () {
          playSavedPlaylistItem(playlist, index);
        });

        row.addEventListener('keydown', function (e) {
          if (e.target !== row) return;

          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            playSavedPlaylistItem(playlist, index);
          }
        });

        removeBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          removePlaylistItemFromDetail(playlist, index);
        });

        frag.appendChild(row);
      });

      playlistDetailList.appendChild(frag);
    }
  }

  playlistDetailModal.classList.add('active');
  playlistDetailModal.setAttribute('aria-hidden', 'false');
  lockUI();
}

async function removePlaylistItemFromDetail(playlist, index) {
  if (!playlist || !playlist.id) return;

  const item = (playlist.items || [])[index];
  const title = item ? (item.title || titleFromFilename(item.name)) : 'este video';

  if (!(await showConfirmDialog('¿Quitar "' + title + '" de esta playlist?', { title: 'Quitar video', okLabel: 'Quitar' }))) return;

  try {
    const result = await apiRemovePlaylistItem(playlist.id, index);
    savedPlaylists = Array.isArray(result.playlists) ? result.playlists : savedPlaylists;
    const updatedPlaylist = result.playlist || savedPlaylists.find(function (saved) {
      return saved.id === playlist.id;
    });

    if (playlistReturnContext && playlistReturnContext.playlist && playlistReturnContext.playlist.id === playlist.id) {
      playlistReturnContext.playlist = updatedPlaylist || null;
    }

    if (updatedPlaylist) {
      activePlaylistDetail = updatedPlaylist;
      openPlaylistDetailModal(updatedPlaylist);
    }

    showAutoNotice('Video quitado de la playlist.', 'success');
  } catch (e) {
    showAutoNotice(e.message || 'No se pudo quitar el video.', 'error');
  }
}

function playSavedPlaylistItem(playlist, index) {
  const items = (playlist && playlist.items) || [];
  const item = items[index];
  if (!item) return;

  const monitorValue = playlistDetailMonitorSelect ? playlistDetailMonitorSelect.value : (monitorSelect ? monitorSelect.value : '');

  if (playlistDetailMonitorSelect && monitorSelect) {
    monitorSelect.value = monitorValue || monitorSelect.value;
    localStorage.setItem(MONITOR_PREF_KEY, monitorSelect.value);
  }

  playlistReturnContext = {
    playlist: playlist,
    monitorValue: monitorValue
  };

  closePlaylistDetailModal();
  stopPlayAll();
  currentMediaType = 'video';
  if (mediaTypeSelect) {
    mediaTypeSelect.value = 'video';
    localStorage.setItem(FILE_TYPE_PREF_KEY, 'video');
  }

  currentFolder = '';
  folderSelect.value = '';
  library = items.map(function (playlistItem) {
    return Object.assign({ source: 'api', kind: 'video' }, playlistItem);
  });
  searchInput.value = '';
  renderLibrary();
  openVideo(index);
}

async function deleteSavedPlaylistFromList(playlist) {
  if (!playlist) return;

  if (!(await showConfirmDialog('¿Eliminar "' + (playlist.name || 'esta playlist') + '"?', { title: 'Eliminar playlist', okLabel: 'Eliminar' }))) return;

  try {
    const result = await apiDeletePlaylist(playlist.id);
    savedPlaylists = Array.isArray(result.playlists) ? result.playlists : [];

    if (playlistReturnContext && playlistReturnContext.playlist && playlistReturnContext.playlist.id === playlist.id) {
      playlistReturnContext = null;
    }

    renderSavedPlaylists();
    showAutoNotice('Playlist eliminada.', 'success');
  } catch (e) {
    showAutoNotice(e.message || 'No se pudo eliminar la playlist.', 'error');
  }
}

async function deleteActivePlaylist() {
  if (!activePlaylistDetail || !playlistDeleteBtn) return;

  const playlistName = activePlaylistDetail.name || 'esta playlist';

  if (!(await showConfirmDialog('¿Eliminar "' + playlistName + '"?', { title: 'Eliminar playlist', okLabel: 'Eliminar' }))) return;

  playlistDeleteBtn.disabled = true;

  try {
    const result = await apiDeletePlaylist(activePlaylistDetail.id);
    savedPlaylists = Array.isArray(result.playlists) ? result.playlists : [];
    if (playlistReturnContext && playlistReturnContext.playlist && playlistReturnContext.playlist.id === activePlaylistDetail.id) {
      playlistReturnContext = null;
    }
    closePlaylistDetailModal();
    showAutoNotice('Playlist eliminada.', 'success');
  } catch (e) {
    showAutoNotice(e.message || 'No se pudo eliminar la playlist.', 'error');
  } finally {
    playlistDeleteBtn.disabled = false;
  }
}

function lockUI() {
  document.getElementById('uiOverlay').classList.add('active');
}

function unlockUI() {
  document.getElementById('uiOverlay').classList.remove('active');
}

// ─── Vista previa del controlador ────────────────────────────────────────────
// Cuando el medio se muestra en OTRA pantalla, el controlador enseña una
// miniatura en vivo para ver cómo se comporta sin mirar la pantalla 2.
const controllerPreview = document.getElementById('controllerPreview');
const controllerPreviewVideo = document.getElementById('controllerPreviewVideo');
const controllerPreviewImg = document.getElementById('controllerPreviewImg');

if (controllerPreviewVideo) {
  controllerPreviewVideo.addEventListener('waiting', function () {
    if (window.cmPreviewStats) window.cmPreviewStats.stalls++;
  });
}

function clearControllerPreview() {
  if (!controllerPreview) return;
  controllerPreview.classList.add('hidden');
  if (controllerPreviewVideo) {
    try { controllerPreviewVideo.pause(); } catch (e) {}
    controllerPreviewVideo.removeAttribute('src');
    controllerPreviewVideo.load();
    controllerPreviewVideo.classList.add('hidden');
  }
  if (controllerPreviewImg) {
    controllerPreviewImg.removeAttribute('src');
    controllerPreviewImg.classList.add('hidden');
  }
}

function updateControllerPreview(item, mode) {
  if (!controllerPreview) return;
  clearControllerPreview();

  // Solo hay vista previa si el medio va a OTRA pantalla (no la principal) y
  // el archivo es servible por la app (no archivos sueltos del navegador).
  if (!item || item.source === 'browser') return;
  const target = getSelectedMonitorTargetSync();
  if (!target || target.value === 'main') return;

  if (mode === 'image') {
    controllerPreviewImg.classList.add('is-loading');
    controllerPreviewImg.onload = function () {
      controllerPreviewImg.classList.remove('is-loading');
    };
    controllerPreviewImg.src = buildMediaUrl(item);
    controllerPreviewImg.classList.remove('hidden');
    controllerPreview.classList.remove('hidden');
    return;
  }

  // Video: miniatura muda que espera al reproductor real. NO arranca sola:
  // queda en pausa hasta que llega el primer estado del reproductor de la
  // pantalla 2, y ahí se alinea exacto (posición real). Así nunca va adelantada.
  if (mode === 'video' && activePlayerKind === 'native') {
    controllerPreviewVideo.muted = true;
    controllerPreviewVideo.preload = 'auto';
    controllerPreviewVideo.dataset.pendingSync = '1';
    controllerPreviewVideo.classList.add('is-loading'); // fade-in al sincronizar
    controllerPreviewVideo.src = buildMediaUrl(item);
    controllerPreviewVideo.classList.remove('hidden');
    controllerPreview.classList.remove('hidden');
  }
}

// Estadísticas del preview (diagnóstico): saltos duros y esperas de buffer.
window.cmPreviewStats = { hardFixes: 0, stalls: 0 };

function syncControllerPreview(current, paused, stateTs) {
  const v = controllerPreviewVideo;
  if (!v || v.classList.contains('hidden')) return;
  if (v.readyState < 1) return;

  // Posición objetivo con compensación de latencia: el estado se escribió en
  // stateTs (epoch ms del host, mismo reloj); lo que haya pasado desde
  // entonces el video real ya lo avanzó.
  let target = Math.max(0, Number(current) || 0);
  if (!paused && stateTs) {
    target += Math.min(2, Math.max(0, (Date.now() - stateTs) / 1000));
  }

  // Primer estado real del reproductor: alinear y recién ahí arrancar.
  if (v.dataset.pendingSync) {
    if (paused && current <= 0.05) return; // pantalla 2 aún no reproduce
    delete v.dataset.pendingSync;
    // Ventana de asentamiento: mientras el elemento buffea el archivo nuevo
    // reporta posiciones falsas; corregir ahí produce tirones visibles.
    v.dataset.settleUntil = String(performance.now() + 1800);
    v.dataset.lastHardFix = '0';
    v.playbackRate = 1;
    try { v.currentTime = target; } catch (e) {}
    v.classList.remove('is-loading');
    if (!paused) {
      const p0 = v.play();
      if (p0 && typeof p0.catch === 'function') p0.catch(function () {});
    }
    return;
  }

  if (paused) {
    if (!v.paused) v.pause();
    v.playbackRate = 1;
    return;
  }

  if (v.paused) {
    const p = v.play();
    if (p && typeof p.catch === 'function') p.catch(function () {});
  }

  // No tocar la posición cuando: el usuario arrastra la barra (el eco ya
  // saltó), el archivo nuevo está asentándose, o el elemento está buffeando
  // o en medio de un seek. Reasignar currentTime en esos momentos reinicia
  // la descarga y deja el preview pegado en bucle.
  const inSeekLock = performance.now() < nativeSeekLockUntil;
  const settling = Number(v.dataset.settleUntil || 0) > performance.now();
  if (inSeekLock || settling || v.seeking || v.readyState < 3) {
    v.playbackRate = 1;
    return;
  }

  const delta = (v.currentTime || 0) - target; // + adelantado, − atrasado
  const abs = Math.abs(delta);
  const now = performance.now();

  if (abs > 1.5) {
    // Error grande: salto duro, pero máximo uno cada 2.5 s (si el elemento
    // no logra buffear, insistir solo lo re-atasca).
    if (now - Number(v.dataset.lastHardFix || 0) > 2500) {
      v.dataset.lastHardFix = String(now);
      window.cmPreviewStats.hardFixes++;
      try { v.currentTime = target; } catch (e) {}
    }
    v.playbackRate = 1;
  } else if (abs > 0.3) {
    // Error pequeño: corregir con velocidad, no con saltos. Ganancia suave y
    // tope ±10%: imperceptible (la posición de WMP trae ±0.3s de ruido, no
    // vale la pena perseguirlo agresivamente).
    const rate = 1 - Math.max(-0.1, Math.min(0.1, delta * 0.2));
    v.playbackRate = rate;
  } else {
    v.playbackRate = 1;
  }
}

function showController(title, mode, item) {
  controllerModal.classList.add('active');
  controllerModal.classList.toggle('image-mode', mode === 'image');
  songTitle.textContent = title || 'Reproduciendo';
  songTitle.title = title || '';
  setPlayPauseButton(false, false);
  if (mode === 'image') playPauseBtn.textContent = '';
  updateControllerPreview(item, mode === 'image' ? 'image' : 'video');
  lockUI();
}

function hideController() {
  controllerModal.classList.remove('active');
  controllerModal.classList.remove('image-mode');
  songTitle.title = '';
  setPlayPauseButton(false, false);
  currentTimeEl.textContent = '00:00';
  durationEl.textContent = '00:00';
  progressBar.value = 0;
  clearControllerPreview();
  unlockUI();
  stopPlayAll();
}

function setPlayPauseButton(paused, pending) {
  isPaused = Boolean(paused);
  playPauseBtn.textContent = '';
  playPauseBtn.classList.toggle('is-paused', isPaused);
  playPauseBtn.classList.toggle('is-playing', !isPaused);
  playPauseBtn.classList.toggle('is-pending', Boolean(pending));
  playPauseBtn.setAttribute('aria-label', isPaused ? 'Reproducir' : 'Pausar');
  playPauseBtn.title = isPaused ? 'Reproducir' : 'Pausar';
}

// ─── Download modal (no tocar) ────────────────────────────────────────────────

function getDownloadUrlKind(url) {
  if (!url) return 'unknown';

  let parsed;

  try {
    parsed = new URL(url);
  } catch (e) {
    return 'unknown';
  }

  const host = parsed.hostname.toLowerCase();
  const isYouTube = host.includes('youtube.com') || host.includes('youtu.be') || host.includes('music.youtube.com');
  const isSocialVideo = host.includes('instagram.com') ||
    host.includes('facebook.com') ||
    host.includes('fb.watch');

  if (isSocialVideo) return 'video';
  if (!isYouTube) return 'unknown';

  const listId = (parsed.searchParams.get('list') || '').toUpperCase();
  const hasAutoRadio = parsed.searchParams.has('start_radio');

  if ((listId && listId.startsWith('RD')) || hasAutoRadio) return 'video';
  if (listId) return 'playlist';

  return 'video';
}

function isUnsupportedFacebookReelUrl(url) {
  let parsed;

  try {
    parsed = new URL(url);
  } catch (e) {
    return false;
  }

  const host = parsed.hostname.toLowerCase();
  const path = parsed.pathname.replace(/\/+$/, '').toLowerCase();

  return host.includes('facebook.com') && path === '/reel';
}

function setDownloadTypeOptions(kind) {
  if (!downloadTypeSelect) return;

  const previous = downloadTypeSelect.value || 'video';
  const optionsByKind = {
    video: [
      ['video', 'Video específico']
    ],
    playlist: [
      ['video', 'Video específico'],
      ['playlist', 'Playlist de usuario']
    ],
    unknown: [
      ['video', 'Video específico'],
      ['playlist', 'Playlist de usuario']
    ]
  };

  const options = optionsByKind[kind] || optionsByKind.unknown;
  const allowed = new Set(options.map(function (item) { return item[0]; }));

  downloadTypeSelect.innerHTML = '';

  for (const option of options) {
    const node = document.createElement('option');
    node.value = option[0];
    node.textContent = option[1];
    downloadTypeSelect.appendChild(node);
  }

  downloadTypeSelect.value = allowed.has(previous) ? previous : 'video';
}

function resetDownloadFormFields() {
  if (downloadUrlInput) downloadUrlInput.value = '';
  if (downloadTypeSelect) {
    downloadTypeSelect.disabled = false;
    setDownloadTypeOptions('unknown');
    downloadTypeSelect.value = 'video';
  }
}

function syncDownloadTypeControls(showNotice) {
  if (!downloadTypeSelect) return;

  setDownloadTypeOptions(getDownloadUrlKind(downloadUrlInput ? downloadUrlInput.value.trim() : ''));
  updateDownloadStartButtonState();
}

function canStartDownloadNow() {
  if (activeDownloadJob) return false;
  if (!downloadFolderSelect || !downloadUrlInput) return false;
  if (!downloadFolderSelect.value) return false;

  const url = downloadUrlInput.value.trim();

  if (!url) return false;
  try {
    new URL(url);
  } catch (e) {
    return false;
  }
  if (isUnsupportedFacebookReelUrl(url)) return false;

  return true;
}

function updateDownloadStartButtonState() {
  if (!downloadStartBtn) return;
  downloadStartBtn.disabled = !canStartDownloadNow();
}

function openDownloadHelpModal() {
  if (!downloadHelpModal) return;

  downloadHelpModal.classList.add('active');
  downloadHelpModal.setAttribute('aria-hidden', 'false');
  if (downloadHelpCloseBtn) downloadHelpCloseBtn.focus();
}

function closeDownloadHelpModal() {
  if (!downloadHelpModal) return;

  downloadHelpModal.classList.remove('active');
  downloadHelpModal.setAttribute('aria-hidden', 'true');
}

function openDownloadModal() {
  // Sin conexión no tiene sentido entrar: aviso claro y no se abre el modal.
  if (!navigator.onLine) {
    showConfirmDialog(
      'Para usar la función de descargar videos necesitas estar conectado a internet. ' +
      'Conéctate a una red e inténtalo de nuevo.',
      { title: 'Sin conexión a internet', okLabel: 'Entendido', hideCancel: true }
    );
    return;
  }

  renderDownloadFolderOptions();
  resetDownloadProgress();

  setDownloadMessage('Pega el enlace del video y selecciona la carpeta donde se guardará.', 'info');

  downloadStartBtn.disabled = true;
  downloadCancelBtn.disabled = false;
  downloadFolderSelect.disabled = false;
  downloadUrlInput.disabled = false;
  resetDownloadFormFields();
  updateDownloadStartButtonState();

  activeDownloadJob = null;
  activeDownloadFolder = '';

  downloadModal.classList.add('active');
  downloadModal.setAttribute('aria-hidden', 'false');

  lockUI();

  setTimeout(function () {
    downloadUrlInput.focus();
  }, 80);
}

function closeDownloadModal() {
  if (downloadPollTimer) {
    clearInterval(downloadPollTimer);
    downloadPollTimer = null;
  }

  activeDownloadJob = null;
  activeDownloadFolder = '';
  closeDownloadHelpModal();
  resetDownloadFormFields();
  resetDownloadProgress();

  downloadModal.classList.remove('active');
  downloadModal.setAttribute('aria-hidden', 'true');

  unlockUI();
}

async function cancelActiveDownload() {
  if (!activeDownloadJob) {
    closeDownloadModal();
    return;
  }

  const confirmed = await showConfirmDialog('¿Quieres cancelar esta descarga? Se detendrá de inmediato.', { title: 'Cancelar descarga', okLabel: 'Sí, cancelar', cancelLabel: 'Seguir descargando' });

  if (!confirmed) return;

  if (downloadPollTimer) {
    clearInterval(downloadPollTimer);
    downloadPollTimer = null;
  }

  setDownloadMessage('Cancelando descarga...', 'warning');
  downloadCancelBtn.disabled = true;

  try {
    const form = new FormData();
    form.append('job', activeDownloadJob);

    const data = await apiJson('api.php?action=download_cancel', {
      method: 'POST',
      body: form
    });

    if (data.error) throw new Error(data.error);

    setDownloadMessage('Descarga cancelada.', 'warning');
    statusText.textContent = 'Descarga cancelada.';
    activeDownloadJob = null;
    activeDownloadFolder = '';

    setTimeout(closeDownloadModal, 650);
  } catch (e) {
    setDownloadMessage(e.message || 'No se pudo cancelar la descarga.', 'error');
    downloadCancelBtn.disabled = false;
  }
}

async function refreshAfterDownload(folder) {
  searchInput.value = '';

  if (currentFolder === '__all__') {
    await loadVideos('__all__');
    return;
  }

  if (folder && foldersCache.some(function (f) { return f.path === folder; })) {
    currentFolder = folder;
    folderSelect.value = folder;
    localStorage.setItem(FOLDER_PREF_KEY, folder);
    await loadVideos(folder);
  } else if (currentFolder) {
    await loadVideos(currentFolder);
  }
}

async function startDownload() {
  // Sin conexión no se puede descargar (yt-dlp necesita internet). Aviso claro.
  if (!navigator.onLine) {
    showConfirmDialog(
      'Para descargar videos necesitas estar conectado a internet. ' +
      'Conéctate a una red e inténtalo de nuevo.',
      { title: 'Sin conexión a internet', okLabel: 'Entendido', hideCancel: true }
    );
    return;
  }

  if (!canStartDownloadNow()) {
    updateDownloadStartButtonState();
    return;
  }

  const folder = downloadFolderSelect.value;
  const url = downloadUrlInput.value.trim();

  if (!folder) {
    setDownloadMessage('Primero selecciona la carpeta donde se guardará el video.', 'error');
    return;
  }

  if (!url) {
    setDownloadMessage('Pega el enlace del video o playlist antes de descargar.', 'error');
    downloadUrlInput.focus();
    return;
  }

  if (isUnsupportedFacebookReelUrl(url)) {
    const message = 'Ese primer reel de Facebook no trae un enlace descargable. Abre otro reel o copia el enlace del video con identificador.';
    setTemporaryDownloadMessage(message, 'error', 6200);
    showAutoNotice(message, 'warning', 6200);
    setTimeout(function () {
      if (!activeDownloadJob) closeDownloadModal();
    }, 6200);
    return;
  }

  syncDownloadTypeControls(false);
  const downloadType = downloadTypeSelect ? downloadTypeSelect.value : 'video';
  const playlistLimit = 0;

  if (downloadPollTimer) {
    clearInterval(downloadPollTimer);
    downloadPollTimer = null;
  }

  activeDownloadJob = null;
  activeDownloadFolder = '';

  downloadProgressWrap.classList.add('hidden');
  setDownloadProgress(0, true);
  downloadLogTail.textContent = '';

  setDownloadMessage('Revisando enlace y calidad...', 'info');
  downloadProgressWrap.classList.remove('hidden');
  downloadProgressWrap.classList.add('is-active');
  setDownloadProgress(0, true);
  if (downloadProgressLabel) downloadProgressLabel.textContent = 'Preparando descarga';
  if (downloadProgressDetail) downloadProgressDetail.textContent = '';

  downloadStartBtn.disabled = true;
  downloadCancelBtn.disabled = false;
  downloadFolderSelect.disabled = true;
  downloadUrlInput.disabled = true;
  if (downloadTypeSelect) downloadTypeSelect.disabled = true;

  try {
    const form = new FormData();
    form.append('folder', folder);
    form.append('url', url);
    form.append('downloadType', downloadType);
    form.append('playlistLimit', String(playlistLimit));

    const data = await apiJson('api.php?action=download_start', {
      method: 'POST',
      body: form
    });

    if (data.error) throw new Error(data.error);
    if (!data.jobId) throw new Error('download_start no devolvió jobId válido.');

    activeDownloadJob = String(data.jobId).trim();
    activeDownloadFolder = data.folder || folder;

    setDownloadMessage('Puedes cancelar la descarga si lo necesitas.', 'info');

    downloadPollTimer = setInterval(pollDownloadStatus, 250);
    pollDownloadStatus();
  } catch (e) {
    stopDownloadLiveProgress();
    downloadProgressWrap.classList.remove('is-active');
    setDownloadMessage(e.message || 'No se pudo iniciar la descarga.', 'error');
    downloadStartBtn.disabled = false;
    downloadFolderSelect.disabled = false;
    downloadUrlInput.disabled = false;
    if (downloadTypeSelect) downloadTypeSelect.disabled = false;
    syncDownloadTypeControls();
  }
}

async function pollDownloadStatus() {
  if (!activeDownloadJob) {
    if (downloadPollTimer) {
      clearInterval(downloadPollTimer);
      downloadPollTimer = null;
    }
    return;
  }

  try {
    const statusUrl = 'api.php?action=download_status&job=' + encodeURIComponent(activeDownloadJob);
    const data = await apiJson(statusUrl);

    if (data.error) throw new Error(data.error + ' | job=' + activeDownloadJob);

    const itemProgress = Number(data.itemProgress ?? data.progress) || 0;
    const totalProgress = Number(data.progress) || 0;
    const itemIndex = Number(data.itemIndex) || 1;
    const itemCount = Number(data.itemCount) || 1;
    const itemTitle = String(data.itemTitle || '').trim();
    const displayProgress = itemCount > 1 ? totalProgress : itemProgress;

    downloadProgressWrap.classList.remove('hidden');
    setDownloadProgress(displayProgress, false, itemCount);

    if (downloadProgressLabel) {
      downloadProgressLabel.textContent = itemCount > 1
        ? 'Video ' + itemIndex + ' de ' + itemCount
        : 'Descargando';
    }

    if (downloadProgressDetail) {
      const details = [];

      if (itemTitle) {
        details.push(itemTitle.length > 58 ? itemTitle.slice(0, 55) + '...' : itemTitle);
      }

      downloadProgressDetail.textContent = details.join(' | ');
    }

    if (data.logTail) downloadLogTail.textContent = data.logTail;
    if (!data.done) return;

    if (downloadPollTimer) {
      clearInterval(downloadPollTimer);
      downloadPollTimer = null;
    }
    stopDownloadLiveProgress();
    downloadProgressWrap.classList.remove('is-active');

    if (data.exitCode === 0) {
      setDownloadProgress(100, true);
      setDownloadMessage('Descarga completada. Actualizando carpeta...', 'success');

      const targetFolder = activeDownloadFolder;
      activeDownloadJob = null;
      setTimeout(async function () {
        closeDownloadModal();
        await refreshAfterDownload(targetFolder);
        statusText.textContent = 'Descarga completada y carpeta actualizada.';
      }, 850);
    } else {
      stopDownloadLiveProgress();
      downloadProgressWrap.classList.remove('is-active');
      const detail = data.logTail
        ? ' Detalle: ' + data.logTail.replace(/\s+/g, ' ').slice(-260)
        : '';
      setDownloadMessage('La descarga terminó con error.' + detail, 'error');
      activeDownloadJob = null;
      activeDownloadFolder = '';
      downloadStartBtn.disabled = false;
      downloadFolderSelect.disabled = false;
      downloadUrlInput.disabled = false;
      if (downloadTypeSelect) downloadTypeSelect.disabled = false;
      syncDownloadTypeControls();
    }
  } catch (e) {
    if (downloadPollTimer) {
      clearInterval(downloadPollTimer);
      downloadPollTimer = null;
    }

    stopDownloadLiveProgress();
    downloadProgressWrap.classList.remove('is-active');
    setDownloadMessage(e.message || 'No se pudo leer el estado de la descarga.', 'error');
    downloadStartBtn.disabled = false;
    downloadFolderSelect.disabled = false;
    downloadUrlInput.disabled = false;
    if (downloadTypeSelect) downloadTypeSelect.disabled = false;
    syncDownloadTypeControls();
  }
}

// ─── Player: ventana de presentación ─────────────────────────────────────────
//
//  CAMBIO IMPORTANTE:
//  Ya no dependemos de window.open() para intentar simular pantalla completa.
//  Chrome no garantiza que window.open() quite barras ni respete siempre moveTo/resizeTo.
//
//  Ahora app.js le pide a api.php que lance player.html como ventana tipo app de
//  Chrome (--app) y un helper local de PowerShell quita el marco de Windows,
//  mueve y redimensiona la ventana exactamente al monitor seleccionado.
//
//  NO se usa requestFullscreen() en ningún lugar.

let activePlayerSid = '';
let activePlayerKind = '';
let playerAlive = false;
let playerLaunching = false;
let pendingPlayerItem = null;
let playerReadyWatchdogTimer = null;
let playerWindowRef = null;
let nativeStatePollTimer = null;
let nativeSeekPreviewValue = null;
let nativeSeekLockUntil = 0;
let nativeSeekTimer = null;
let playPausePendingUntil = 0;
let endedHandledSid = '';
let lastNativeNavId = '';
const PLAYER_COMMAND_PREFIX = 'control-musica.command.';

function makePlayerSid() {
  return 'cm_' + Date.now() + '_' + Math.random().toString(16).slice(2);
}

function isPlayerOpen() {
  if (playerWindowRef && playerWindowRef.closed) {
    playerAlive = false;
    playerWindowRef = null;
  }

  return Boolean(playerAlive && activePlayerSid);
}

/**
 * Solicita el permiso "window-management" de Chrome.
 * Esto sigue siendo útil para detectar correctamente los monitores desde JS.
 */
async function requestWindowMgmtPermission() {
  if (!('getScreenDetails' in window)) return;

  try {
    const status = await navigator.permissions.query({ name: 'window-management' });

    if (status.state === 'granted') return;

    if (status.state === 'prompt') {
      await window.getScreenDetails();
    }

    if (status.state === 'denied') {
      statusText.textContent =
        '⚠ Permiso de pantallas denegado. Ve a Configuración del sitio → Administración de ventanas → Permitir.';
    }
  } catch (e) {
    console.warn('[Monitor] requestWindowMgmtPermission:', e.message);
  }
}

function getSelectedMonitorTargetSync() {
  return (
    monitorTargets.find(function (s) { return s.value === monitorSelect.value; }) ||
    monitorTargets[0] ||
    getFallbackTargets()[0]
  );
}

function buildPlayerActionUrl(action, sid, target, item) {
  const params = new URLSearchParams();
  params.set('action', action);
  params.set('sid', sid || activePlayerSid);

  if (target) {
    params.set('left', String(Math.round(target.left)));
    params.set('top', String(Math.round(target.top)));
    params.set('width', String(Math.round(target.width)));
    params.set('height', String(Math.round(target.height)));
  }

  if (item && action === 'launch_player') {
    params.set('folder', item.folder || '');
    params.set('file', item.name || '');
    params.set('source', item.source || 'api');
    params.set('type', item.kind || currentMediaType);
    params.set('title', item.title || item.name || '');
  }

  return 'api.php?' + params.toString();
}

async function playerApi(action, sid, target, item) {
  return apiJson(buildPlayerActionUrl(action, sid, target, item));
}

async function nativePlayerCommand(type, extra) {
  if (!activePlayerSid || activePlayerKind !== 'native') return null;

  const form = new FormData();
  form.append('type', type);

  if (extra && typeof extra.time !== 'undefined') {
    form.append('time', String(extra.time));
  }

  if (extra && extra.item) {
    form.append('folder', extra.item.folder || '');
    form.append('file', extra.item.name || '');
    form.append('mediaType', extra.item.kind || currentMediaType);

    // Coordenadas del monitor elegido: el host se reposiciona en cada load.
    const target = (extra && extra.target) || getSelectedMonitorTargetSync();
    if (target) {
      form.append('left', String(Math.round(target.left)));
      form.append('top', String(Math.round(target.top)));
      form.append('width', String(Math.round(target.width)));
      form.append('height', String(Math.round(target.height)));
    }
  }

  return apiJson('api.php?action=native_player_command&sid=' + encodeURIComponent(activePlayerSid), {
    method: 'POST',
    body: form
  });
}

// Hook de diagnóstico (solo lectura) del estado interno del reproductor.
window.cmDebugState = function () {
  return {
    sid: activePlayerSid,
    kind: activePlayerKind,
    mode: playbackMode,
    alive: playerAlive,
    launching: playerLaunching,
    polling: Boolean(nativeStatePollTimer),
    prewarm: prewarmNativeSid,
    stats: window.cmPreviewStats
  };
};

function stopNativeStatePolling() {
  if (nativeStatePollTimer) {
    clearInterval(nativeStatePollTimer);
    nativeStatePollTimer = null;
  }

  if (nativeSeekTimer) {
    clearTimeout(nativeSeekTimer);
    nativeSeekTimer = null;
  }

  nativeSeekPreviewValue = null;
  nativeSeekLockUntil = 0;
}

function resetNativeSessionState() {
  playerAlive = false;
  playerLaunching = false;
  activePlayerSid = '';
  activePlayerKind = '';
  endedHandledSid = '';
  pendingPlayerItem = null;
  playerWindowRef = null;
  lastNativeNavId = '';
  stopNativeStatePolling();

  if (playbackMode === 'external' || playbackMode === 'image') {
    playbackMode = '';
  }

  // Teardown universal (ESC, fin natural, botón quitar, cambio de modo):
  // dejar listo un host precalentado para que el próximo video sea inmediato.
  schedulePrewarmNativePlayer(1200);
}

function handleNativeClosed(state) {
  const wasEnded = Boolean(state && state.ended);
  const wasPlaylist = isPlayAllMode;

  resetNativeSessionState();

  if (wasEnded && wasPlaylist) {
    statusText.textContent = 'Video finalizado. Reproduciendo siguiente...';
    setTimeout(playNext, 120);
    return;
  }

  if (controllerModal.classList.contains('active')) {
    hideController();
  } else if (!wasPlaylist) {
    stopPlayAll();
  }

  if (returnToPlaylistDetail()) return;

  setIdleStatus();
}

function applyNativeState(state) {
  if (!state || activePlayerKind !== 'native' || (playbackMode !== 'external' && playbackMode !== 'image')) return;

  if (state.closed) {
    handleNativeClosed(state);
    return;
  }

  const navId = String(state.navId || '');
  const nav = String(state.nav || '');

  if (playbackMode === 'image' && navId && navId !== lastNativeNavId) {
    lastNativeNavId = navId;
    if (nav === 'next') {
      playNext();
    } else if (nav === 'prev') {
      playPrev();
    }
    return;
  }

  if (playbackMode !== 'external') return;

  const duration = Number(state.duration) || 0;
  const current = Number(state.current) || 0;
  const now = performance.now();
  const hasSeekPreview = nativeSeekPreviewValue !== null;
  const preview = Number(nativeSeekPreviewValue) || 0;

  durationEl.textContent = formatTime(duration);
  progressBar.max = duration || 0;

  if (hasSeekPreview && now < nativeSeekLockUntil && Math.abs(current - preview) > 0.7) {
    currentTimeEl.textContent = formatTime(preview);
    progressBar.value = Math.min(duration || preview, preview);
  } else {
    nativeSeekPreviewValue = null;
    nativeSeekLockUntil = 0;
    currentTimeEl.textContent = formatTime(current);
    progressBar.value = Math.min(duration || current, current);
  }

  if (performance.now() >= playPausePendingUntil) {
    setPlayPauseButton(Boolean(state.paused), false);
  }

  // Mantener la miniatura del controlador alineada con el reproductor real.
  syncControllerPreview(current, Boolean(state.paused), Number(state.ts) || 0);

  if (state.error) {
    statusText.textContent = 'No se pudo reproducir el video: ' + state.error;
  }

  if (state.ended && endedHandledSid !== activePlayerSid) {
    endedHandledSid = activePlayerSid;
    handlePlaybackEnded();
  }
}

function startNativeStatePolling() {
  stopNativeStatePolling();

  if (!activePlayerSid || activePlayerKind !== 'native') return;

  nativeStatePollTimer = setInterval(async function () {
    if (!activePlayerSid || activePlayerKind !== 'native') {
      stopNativeStatePolling();
      return;
    }

    try {
      const data = await apiJson('api.php?action=native_player_state&sid=' + encodeURIComponent(activePlayerSid));
      if (data && data.state) applyNativeState(data.state);
    } catch (e) {
      console.warn('[Player nativo] No se pudo leer estado:', e.message);
    }
  }, 300);
}

function queueNativeSeek(value, immediate) {
  const nextValue = Math.max(0, Number(value) || 0);

  nativeSeekPreviewValue = nextValue;
  nativeSeekLockUntil = performance.now() + 1400;
  currentTimeEl.textContent = formatTime(nextValue);
  progressBar.value = nextValue;

  // Eco inmediato en la miniatura: se ve el salto al instante, sin esperar
  // la confirmación del reproductor real.
  if (controllerPreviewVideo && !controllerPreviewVideo.classList.contains('hidden') &&
      controllerPreviewVideo.readyState >= 1 && !controllerPreviewVideo.dataset.pendingSync) {
    try { controllerPreviewVideo.currentTime = nextValue; } catch (e) {}
  }

  if (nativeSeekTimer) {
    clearTimeout(nativeSeekTimer);
    nativeSeekTimer = null;
  }

  const send = function () {
    nativeSeekTimer = null;
    nativePlayerCommand('seek', { time: nextValue }).catch(function (e) {
      statusText.textContent = e.message || 'No se pudo mover el reproductor.';
    });
  };

  if (immediate) {
    send();
  } else {
    nativeSeekTimer = setTimeout(send, 90);
  }
}

function buildPlayerWindowUrl(sid, item) {
  const url = new URL('player.html', window.location.href);
  url.searchParams.set('sid', sid);
  url.searchParams.set('v', '4');

  if (item) {
    url.searchParams.set('folder', item.folder || '');
    url.searchParams.set('file', item.name || '');
    url.searchParams.set('source', item.source || 'api');
    url.searchParams.set('type', item.kind || currentMediaType);
    url.searchParams.set('title', item.title || item.name || '');
  }

  return url.toString();
}

function buildPlayerWindowFeatures(target) {
  return [
    'popup=yes',
    'toolbar=no',
    'location=no',
    'menubar=no',
    'status=no',
    'scrollbars=no',
    'resizable=yes',
    'left=' + Math.round(target.left),
    'top=' + Math.round(target.top),
    'width=' + Math.round(target.width),
    'height=' + Math.round(target.height)
  ].join(',');
}

function buildNativePlayerActionUrl(sid, target, item) {
  const params = new URLSearchParams();
  params.set('action', 'launch_native_player');
  params.set('sid', sid);
  params.set('folder', item.folder);
  params.set('file', item.name);
  params.set('type', item.kind || currentMediaType);
  params.set('left', String(Math.round(target.left)));
  params.set('top', String(Math.round(target.top)));
  params.set('width', String(Math.round(target.width)));
  params.set('height', String(Math.round(target.height)));
  return 'api.php?' + params.toString();
}

function sendCommandToPlayer(payload) {
  const command = Object.assign({}, payload || {});

  if (!command.sid) {
    command.sid = activePlayerSid;
  }

  command._id = Date.now() + '_' + Math.random().toString(16).slice(2);

  channel.postMessage(command);

  // Respaldo: si BroadcastChannel pierde el primer mensaje por timing,
  // el player también lee este comando desde localStorage.
  try {
    localStorage.setItem(PLAYER_COMMAND_PREFIX + command.sid, JSON.stringify(command));
  } catch (e) {}
}

function stopPlayerReadyWatchdog() {
  if (playerReadyWatchdogTimer) {
    clearInterval(playerReadyWatchdogTimer);
    playerReadyWatchdogTimer = null;
  }
}

function startPlayerReadyWatchdog(item, sid) {
  stopPlayerReadyWatchdog();

  let attempts = 0;
  const readyKey = 'control-musica.ready.' + sid;
  const kind = item.kind || currentMediaType;

  playerReadyWatchdogTimer = setInterval(function () {
    attempts++;

    if (!activePlayerSid || activePlayerSid !== sid || playerAlive) {
      stopPlayerReadyWatchdog();
      return;
    }

    try {
      const rawReady = localStorage.getItem(readyKey);

      if (rawReady) {
        const readyData = JSON.parse(rawReady);

        if (readyData && readyData.sid === sid) {
          playerAlive = true;
          playerLaunching = false;
          stopPlayerReadyWatchdog();

          statusText.textContent = 'Player activo ✓';

          positionPlayerWindow('ready_localStorage');

          if (pendingPlayerItem) {
            const pending = pendingPlayerItem;
            pendingPlayerItem = null;

            setTimeout(function () {
              sendMediaToPlayer(pending);
            }, 180);
          }

          return;
        }
      }
    } catch (e) {}

    sendCommandToPlayer({
      type: 'load',
      sid: sid,
      source: item.source || 'api',
      folder: item.folder,
      file: item.name,
      title: item.title,
      kind: kind
    });

    if (attempts === 2) {
      if (shouldShowPresentationController(item)) {
        showController(item.title, kind === 'image' ? 'image' : 'video', item);
      } else {
        hideController();
      }
      statusText.textContent = kind === 'image' ? 'Enviando imagen al player...' : 'Enviando video al player...';
      activatePlayerWindow(250);
    }

    if (attempts >= 12) {
      stopPlayerReadyWatchdog();
      statusText.textContent = 'El player no confirmó conexión, pero se intentó enviar el video. Revisa la otra pantalla.';
    }
  }, 500);
}

async function launchPlayerWindow(item) {
  if (playerLaunching) return true;

  const target = getSelectedMonitorTargetSync();
  const kind = item && (item.kind || currentMediaType);
  const isImage = kind === 'image';
  const sid = makePlayerSid();
  const playerUrl = buildPlayerWindowUrl(sid, item);
  const features = buildPlayerWindowFeatures(target);
  const openedWindow = window.open(playerUrl, 'CONTROL_MUSICA_PLAYER_' + sid, features);

  if (!openedWindow) {
    statusText.textContent = 'El navegador bloqueó la ventana de presentación. Permite ventanas emergentes para este sitio.';
    return false;
  }

  activePlayerSid = sid;
  activePlayerKind = 'browser';
  playerAlive = true;
  playerLaunching = true;
  playbackMode = isImage ? 'image' : 'external';
  pendingPlayerItem = null;
  playerWindowRef = openedWindow;

  try {
    openedWindow.moveTo(Math.round(target.left), Math.round(target.top));
    openedWindow.resizeTo(Math.round(target.width), Math.round(target.height));
    openedWindow.focus();
  } catch (e) {}

  try {
    playerLaunching = false;
    positionPlayerWindow('launch_direct');

    if (item && shouldShowPresentationController(item)) {
      showController(item.title, isImage ? 'image' : 'video', item);
    } else if (isImage) {
      hideController();
    }

    statusText.textContent = 'Abriendo pantalla de presentación...';
    if (item) {
      statusText.textContent = (isImage ? 'Imagen en pantalla: ' : 'Reproduciendo: ') + item.title;
    }
    return true;
  } catch (e) {
    playerLaunching = false;
    playerAlive = false;
    activePlayerSid = '';
    activePlayerKind = '';
    pendingPlayerItem = null;
    statusText.textContent = e.message || 'No se pudo lanzar la pantalla de presentación.';
    return false;
  }
}

function positionPlayerWindow(reason) {
  if (!activePlayerSid) return;

  const target = getSelectedMonitorTargetSync();

  if (playerWindowRef && !playerWindowRef.closed) {
    try {
      playerWindowRef.moveTo(Math.round(target.left), Math.round(target.top));
      playerWindowRef.resizeTo(Math.round(target.width), Math.round(target.height));
    } catch (e) {}
  }

  playerApi('position_player', activePlayerSid, target).catch(function (e) {
    console.warn('[Player] No se pudo reposicionar (' + (reason || '') + '):', e.message);
  });
}

function activatePlayerWindow(delayMs) {
  if (!activePlayerSid) return;

  setTimeout(function () {
    const target = getSelectedMonitorTargetSync();

    if (playerWindowRef && !playerWindowRef.closed) {
      try {
        playerWindowRef.focus();
      } catch (e) {}
    }

    playerApi('activate_player', activePlayerSid, target).catch(function (e) {
      console.warn('[Player] No se pudo activar por click local:', e.message);
    });
  }, delayMs || 350);
}

function closePlayerWindow() {
  const sid = activePlayerSid;

  if (!sid) return;

  if (activePlayerKind === 'native') {
    nativePlayerCommand('close').catch(function () {});
    stopNativeStatePolling();
  }

  sendCommandToPlayer({
    type: 'close',
    sid: sid
  });

  setTimeout(function () {
    if (playerWindowRef && !playerWindowRef.closed) {
      try { playerWindowRef.close(); } catch (e) {}
    }
    playerApi('close_player', sid).catch(function () {});
  }, 420);

  resetNativeSessionState();
}

function closePlaybackOnPageExit() {
  if (isLocalAudioActive()) {
    try { localAudioPlayer.pause(); } catch (e) {}
  }

  if (!activePlayerSid) return;

  const sid = activePlayerSid;

  try {
    if (activePlayerKind === 'native' && navigator.sendBeacon) {
      const commandForm = new FormData();
      commandForm.append('type', 'close');
      navigator.sendBeacon('api.php?action=native_player_command&sid=' + encodeURIComponent(sid), commandForm);
    }
  } catch (e) {}

  try {
    if (navigator.sendBeacon) {
      navigator.sendBeacon('api.php?action=close_player&sid=' + encodeURIComponent(sid), new FormData());
    } else {
      fetch('api.php?action=close_player&sid=' + encodeURIComponent(sid), { keepalive: true }).catch(function () {});
    }
  } catch (e) {}
}

function stopLocalAudio() {
  if (!isLocalAudioActive() && !localAudioPlayer.src) return;

  localAudioPlayer.pause();
  localAudioPlayer.removeAttribute('src');
  localAudioPlayer.load();

  if (activeLocalAudioUrl) {
    URL.revokeObjectURL(activeLocalAudioUrl);
    activeLocalAudioUrl = '';
  }

  playbackMode = '';
  setPlayPauseButton(false, false);
}

function closeInlineImageViewer() {
  if (!imageViewerOverlay) return;

  imageViewerOverlay.classList.remove('active');
  imageViewerOverlay.setAttribute('aria-hidden', 'true');

  if (imageViewerImg) {
    imageViewerImg.removeAttribute('src');
    imageViewerImg.alt = '';
  }

  if (activeImageViewerUrl) {
    URL.revokeObjectURL(activeImageViewerUrl);
    activeImageViewerUrl = '';
  }

  if (playbackMode === 'image-inline') {
    playbackMode = '';
  }

  setIdleStatus();
}

function preloadInlineImageNeighbors() {
  if (playbackMode !== 'image-inline' || !library.length || currentIndex < 0) return;

  [currentIndex - 1, currentIndex + 1].forEach(function (idx) {
    if (idx < 0 || idx >= library.length) return;
    const item = library[idx];
    if (!item || (item.kind || currentMediaType) !== 'image' || item.source === 'browser') return;

    const img = new Image();
    img.decoding = 'async';
    img.src = buildMediaUrl(item);
  });
}

async function openInlineImageViewer(item) {
  if (!item || !imageViewerOverlay || !imageViewerImg) return false;

  const wasInline = playbackMode === 'image-inline' && imageViewerOverlay.classList.contains('active');

  if (!wasInline) {
    closePlayerWindow();
    closeInlineImageViewer();
  } else if (activeImageViewerUrl) {
    URL.revokeObjectURL(activeImageViewerUrl);
    activeImageViewerUrl = '';
  }

  let imageUrl = '';

  try {
    imageUrl = item.source === 'browser'
      ? await getBrowserFileObjectUrl(item)
      : buildMediaUrl(item);
  } catch (e) {
    statusText.textContent = e.message || 'No se pudo abrir la imagen.';
    return false;
  }

  if (item.source === 'browser') {
    activeImageViewerUrl = imageUrl;
  }

  playbackMode = 'image-inline';
  imageViewerImg.alt = item.title || '';
  imageViewerImg.src = imageUrl;

  if (!wasInline) {
    imageViewerOverlay.classList.add('active');
    imageViewerOverlay.setAttribute('aria-hidden', 'false');
    imageViewerOverlay.focus({ preventScroll: true });
  }

  statusText.textContent = 'Imagen en pantalla: ' + item.title;
  preloadInlineImageNeighbors();

  return true;
}

function syncLocalController() {
  currentTimeEl.textContent = formatTime(localAudioPlayer.currentTime || 0);
  durationEl.textContent = formatTime(localAudioPlayer.duration || 0);
  progressBar.max = Number(localAudioPlayer.duration) || 0;
  progressBar.value = Number(localAudioPlayer.currentTime) || 0;
  setPlayPauseButton(Boolean(localAudioPlayer.paused), false);
}

async function sendVideoToLocalAudio(item) {
  if (!item) return;

  if (isPlayerOpen()) {
    closePlayerWindow();
  }

  if (activeLocalAudioUrl) {
    URL.revokeObjectURL(activeLocalAudioUrl);
    activeLocalAudioUrl = '';
  }

  playbackMode = 'local';
  localAudioPlayer.pause();

  try {
    activeLocalAudioUrl = item.source === 'browser'
      ? await getBrowserFileObjectUrl(item)
      : buildMediaUrl(item);
  } catch (e) {
    playbackMode = '';
    statusText.textContent = e.message || 'No se pudo preparar el audio.';
    return;
  }

  localAudioPlayer.src = activeLocalAudioUrl;
  localAudioPlayer.load();

  showController(item.title);
  statusText.textContent = 'Reproduciendo solo audio: ' + item.title;

  const promise = localAudioPlayer.play();

  if (promise && typeof promise.catch === 'function') {
    promise.catch(function () {
      statusText.textContent = 'Chrome bloqueo el audio. Pulsa reproducir en el controlador.';
      setPlayPauseButton(true, false);
    });
  }
}

function shouldShowPresentationController(item) {
  const kind = item && (item.kind || currentMediaType);
  const target = getSelectedMonitorTargetSync();
  return kind !== 'image' || target.value !== 'main';
}

function sendMediaToPlayer(item) {
  if (!item || !activePlayerSid) return;

  if (isLocalAudioActive()) {
    stopLocalAudio();
  }

  const kind = item.kind || currentMediaType;
  const isImage = kind === 'image';

  activePlayerKind = 'browser';
  playbackMode = isImage ? 'image' : 'external';
  positionPlayerWindow('sendMediaToPlayer');

  sendCommandToPlayer({
    type: 'load',
    sid: activePlayerSid,
    source: item.source || 'api',
    folder: item.folder,
    file: item.name,
    title: item.title,
    kind: kind
  });

  if (shouldShowPresentationController(item)) {
    showController(item.title, isImage ? 'image' : 'video', item);
  } else {
    hideController();
  }

  statusText.textContent = (isImage ? 'Imagen en pantalla: ' : 'Reproduciendo: ') + item.title;

  // Ayuda a vencer el bloqueo de autoplay con audio: el helper local hace un
  // click real dentro de la ventana del player. Si Chrome ya permite autoplay,
  // este click no afecta nada.
  if (!isImage) {
    activatePlayerWindow(350);
    activatePlayerWindow(900);
  }
}

// ─── Pre-calentado del reproductor nativo ────────────────────────────────────
// Arranca el host de video (PowerShell + WMP) con la ventana oculta, ANTES de
// que el usuario pida un video. El primer play se convierte en un simple
// comando 'load' sobre el host ya listo => arranque casi instantáneo.
let prewarmNativeSid = '';
let prewarmNativeTimer = null;

function schedulePrewarmNativePlayer(delayMs) {
  if (prewarmNativeTimer) clearTimeout(prewarmNativeTimer);
  prewarmNativeTimer = setTimeout(function () {
    prewarmNativeTimer = null;
    prewarmNativePlayer();
  }, Math.max(0, delayMs || 0));
}

async function prewarmNativePlayer() {
  if (activePlayerSid || prewarmNativeSid) return;

  const sid = makePlayerSid();
  const target = getSelectedMonitorTargetSync();

  try {
    const data = await apiJson(buildPlayerActionUrl('prewarm_native_player', sid, target));
    if (data && data.ok) {
      prewarmNativeSid = sid;
    }
  } catch (e) {
    console.warn('[Player nativo] Prewarm falló:', e.message);
  }
}

// Devuelve el sid del host precalentado si está vivo y fresco; si no, ''.
async function claimPrewarmedNativePlayer() {
  const sid = prewarmNativeSid;
  if (!sid) return '';
  prewarmNativeSid = '';

  try {
    const data = await apiJson('api.php?action=native_player_state&sid=' + encodeURIComponent(sid));
    const s = data && data.state;
    const fresh = s && !s.closed && Number(s.ts) && (Date.now() - Number(s.ts) < 4000);
    if (fresh) return sid;
  } catch (e) {}

  return '';
}

async function launchWindowsPlayer(item) {
  if (!item) return false;
  if (item.source === 'browser') {
    statusText.textContent = 'Para abrir con el visor de Windows, agrega la carpeta usando su ruta completa.';
    return false;
  }
  const kind = item.kind || currentMediaType;
  const isImage = kind === 'image';

  if (isLocalAudioActive()) {
    stopLocalAudio();
  }

  // Ruta rápida: si hay un host de video precalentado, se adopta y el play es
  // solo un 'load' (el host se revela ya posicionado en el monitor elegido).
  if (!isImage && !activePlayerSid) {
    const warmSid = await claimPrewarmedNativePlayer();
    if (warmSid) {
      activePlayerSid = warmSid;
      activePlayerKind = 'native';
      endedHandledSid = '';
      lastNativeNavId = '';
      playerAlive = true;
      playerLaunching = false;
      playbackMode = 'external';
      pendingPlayerItem = null;

      const loaded = await loadItemInNativePlayer(item);
      if (loaded) return true;

      // El host murió entre medio: limpiar y seguir con el arranque normal.
      resetNativeSessionState();
    }
  }

  if (activePlayerSid) {
    closePlayerWindow();
  }

  const sid = makePlayerSid();
  const target = getSelectedMonitorTargetSync();
  const showImageController = !(isImage && target.value === 'main');

  activePlayerSid = sid;
  activePlayerKind = 'native';
  endedHandledSid = '';
  lastNativeNavId = '';
  playerAlive = true;
  playerLaunching = true;
  playbackMode = isImage ? 'image' : 'external';
  pendingPlayerItem = null;
  statusText.textContent = isImage ? 'Abriendo imagen en pantalla...' : 'Abriendo en el reproductor de Windows...';

  try {
    const data = await apiJson(buildNativePlayerActionUrl(sid, target, item));

    if (data.error) throw new Error(data.error);

    playerLaunching = false;
    if (!isImage || showImageController) {
      showController(item.title, isImage ? 'image' : 'video', item);
    }
    startNativeStatePolling();
    statusText.textContent = (isImage ? 'Imagen en pantalla: ' : 'Reproduciendo en monitor externo: ') + item.title;
    return true;
  } catch (e) {
    playerAlive = false;
    playerLaunching = false;
    activePlayerSid = '';
    activePlayerKind = '';
    playbackMode = '';
    stopNativeStatePolling();
    statusText.textContent = e.message || 'No se pudo abrir el reproductor de Windows.';
    return false;
  }
}

async function loadItemInNativePlayer(item) {
  if (!item || activePlayerKind !== 'native' || !activePlayerSid || item.source === 'browser') {
    return false;
  }

  const kind = item.kind || currentMediaType;

  if ((kind === 'video' && playbackMode !== 'external') || (kind === 'image' && playbackMode !== 'image')) {
    return false;
  }

  if (kind !== 'video' && kind !== 'image') {
    return false;
  }

  try {
    if (kind === 'image') {
      const stateData = await apiJson('api.php?action=native_player_state&sid=' + encodeURIComponent(activePlayerSid));
      if (stateData && stateData.state && stateData.state.closed) {
        handleNativeClosed(stateData.state);
        return false;
      }
    }

    playerAlive = true;
    playerLaunching = false;
    endedHandledSid = '';
    nativeSeekPreviewValue = null;
    nativeSeekLockUntil = 0;
    songTitle.textContent = item.title || item.name || 'Reproduciendo';
    songTitle.title = item.title || item.name || '';

    if (kind === 'video') {
      currentTimeEl.textContent = '00:00';
      durationEl.textContent = '00:00';
      progressBar.value = 0;
    }

    if (shouldShowPresentationController(item)) {
      showController(item.title, kind === 'image' ? 'image' : 'video', item);
    } else if (kind === 'image') {
      hideController();
    }

    statusText.textContent = kind === 'image' ? 'Cambiando imagen...' : 'Cambiando video...';

    const data = await nativePlayerCommand('load', { item: item });

    if (data && data.error) throw new Error(data.error);

    startNativeStatePolling();

    statusText.textContent = (kind === 'image' ? 'Imagen en pantalla: ' : 'Reproduciendo en monitor externo: ') + item.title;
    return true;
  } catch (e) {
    statusText.textContent = e.message || (kind === 'image' ? 'No se pudo cambiar la imagen.' : 'No se pudo cambiar el video.');
    return false;
  }
}

async function openVideo(index) {
  currentIndex = index;
  const item = library[currentIndex];
  if (!item) return;

  if (shouldUseInlineImageViewer(item)) {
    if (isLocalAudioActive()) stopLocalAudio();
    await openInlineImageViewer(item);
    return;
  }

  if (playbackMode === 'image-inline') {
    closeInlineImageViewer();
  }

  if (shouldUseLocalAudio(item)) {
    await sendVideoToLocalAudio(item);
    return;
  }

  if (isLocalAudioActive()) {
    stopLocalAudio();
  }

  if (item.source !== 'browser') {
    if (await loadItemInNativePlayer(item)) return;
    await launchWindowsPlayer(item);
    return;
  }

  if (activePlayerKind === 'native' && activePlayerSid) {
    closePlayerWindow();
  }

  if (!isPlayerOpen()) {
    const opened = await launchPlayerWindow(item);
    if (!opened) return;
    return;
  }

  sendMediaToPlayer(item);
}

// ─── Reproducción secuencial ──────────────────────────────────────────────────

function playNext() {
  if (!library.length) return;
  const next = currentIndex >= library.length - 1 ? 0 : currentIndex + 1;
  openVideo(next);
}

function playPrev() {
  if (!library.length) return;
  const prev = currentIndex <= 0 ? library.length - 1 : currentIndex - 1;
  openVideo(prev);
}

function startPlayAll() {
  if (!library.length) return;

  isPlayAllMode = true;
  playAllBtn.classList.add('active');
  playAllBtn.setAttribute('aria-pressed', 'true');

  // Siempre empieza desde la primera canción (índice 0, orden alfabético)
  currentIndex = 0;
  openVideo(0);
}

function stopPlayAll() {
  isPlayAllMode = false;

  if (playAllBtn) {
    playAllBtn.classList.remove('active');
    playAllBtn.setAttribute('aria-pressed', 'false');
  }
}

// ─── Eventos de controles ─────────────────────────────────────────────────────

folderSelect.addEventListener('change', function (e) {
  currentFolder = e.target.value;
  if (currentFolder === TEMP_PLAYLIST_ID) {
    localStorage.removeItem(FOLDER_PREF_KEY);
  } else {
    localStorage.setItem(FOLDER_PREF_KEY, currentFolder);
  }
  loadVideos(currentFolder);
  renderDownloadFolderOptions();
});

if (mediaTypeSelect) {
  mediaTypeSelect.addEventListener('change', function () {
    currentMediaType = mediaTypeSelect.value === 'image' ? 'image' : 'video';
    localStorage.setItem(FILE_TYPE_PREF_KEY, currentMediaType);
    searchInput.value = '';
    stopPlayAll();
    if (activePlayerSid) closePlayerWindow();
    if (playbackMode === 'image-inline') closeInlineImageViewer();
    if (currentFolder === TEMP_PLAYLIST_ID && currentMediaType !== 'video') {
      currentFolder = '';
      folderSelect.value = '';
    }
    updateMediaTypeUi();
    loadVideos(currentFolder);
  });
}

var searchDebounceTimer = null;
searchInput.addEventListener('input', function () {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(function () {
    searchDebounceTimer = null;
    renderLibrary();
  }, 150);
});

// Delegacion de eventos: un unico listener en #grid maneja el click de todas las
// tarjetas (antes se anadia uno por tarjeta en cada render).
grid.addEventListener('click', function (e) {
  const card = e.target.closest('.video-card');
  if (!card || !grid.contains(card)) return;

  const idx = library.findIndex(function (v) {
    return String(v.folder) === card.dataset.folder && String(v.name) === card.dataset.name;
  });
  if (idx < 0) return;

  // Detener modo "Reproducir carpeta" si estaba activo
  if (isPlayAllMode) stopPlayAll();

  openVideo(idx);
});

window.addEventListener('focus', function () {
  renderMonitorOptions({ silent: true, notice: true }).catch(function (e) {
    console.warn('[Monitor] No se pudo sincronizar al enfocar:', e.message);
  });

  if (currentFolder && !isPlayAllMode && !controllerModal.classList.contains('active')) {
    loadFolders(currentFolder, { notice: true, skipAutoLoad: true }).then(function () {
      if (currentFolder) loadVideos(currentFolder, { skipFolderRefresh: true, silent: true });
    });
  }
});

setInterval(function () {
  // Antes se excluía el tipo imagen (currentMediaType === 'image'), por lo que
  // las imágenes agregadas por fuera no se reflejaban en vivo. Ahora también se
  // refrescan; el chequeo de firma (silent) evita re-render si nada cambió.
  if (document.hidden || !currentFolder || isPlayAllMode || controllerModal.classList.contains('active')) return;

  loadFolders(currentFolder, { notice: true, skipAutoLoad: true }).then(function () {
    if (currentFolder) loadVideos(currentFolder, { skipFolderRefresh: true, silent: true });
  }).catch(function (e) {
    console.warn('[Carpetas] No se pudo sincronizar en segundo plano:', e.message);
  });
}, 30000);

setInterval(function () {
  if (document.hidden) return;

  renderMonitorOptions({ silent: true, notice: true }).catch(function (e) {
    console.warn('[Monitor] No se pudo sincronizar en segundo plano:', e.message);
  });
}, 30000);

monitorSelect.addEventListener('change', function () {
  localStorage.setItem(MONITOR_PREF_KEY, monitorSelect.value);
  // Si el player está abierto, reposicionarlo al nuevo monitor inmediatamente
  if (isPlayerOpen()) {
    positionPlayerWindow('monitorSelect_change');
  }
});

playPauseBtn.addEventListener('click', function () {
  if (playbackMode === 'image') return;

  if (isLocalAudioActive()) {
    if (localAudioPlayer.paused) {
      const promise = localAudioPlayer.play();
      if (promise && typeof promise.catch === 'function') {
        promise.catch(function () {
          statusText.textContent = 'No se pudo reanudar el audio.';
        });
      }
    } else {
      localAudioPlayer.pause();
    }

    syncLocalController();
    return;
  }

  if (activePlayerKind === 'native') {
    if (performance.now() < playPausePendingUntil) return;
    const nextPaused = !isPaused;
    playPausePendingUntil = performance.now() + 900;
    setPlayPauseButton(nextPaused, true);
    nativePlayerCommand(nextPaused ? 'pause' : 'play').then(function () {
      playPausePendingUntil = 0;
      setPlayPauseButton(nextPaused, false);
    }).catch(function (e) {
      playPausePendingUntil = 0;
      setPlayPauseButton(!nextPaused, false);
      statusText.textContent = e.message || 'No se pudo controlar el reproductor.';
    });
    return;
  }

  const nextPaused = !isPaused;
  setPlayPauseButton(nextPaused, false);
  sendCommandToPlayer({ type: nextPaused ? 'pause' : 'play', sid: activePlayerSid });
});

closeBtn.addEventListener('click', function () {
  if (playbackMode === 'image-inline') {
    closeInlineImageViewer();
    hideController();
    return;
  }

  if (isLocalAudioActive()) {
    stopLocalAudio();
  } else {
    closePlayerWindow();
  }

  hideController();
  if (returnToPlaylistDetail()) return;

  setIdleStatus();
});

nextBtn.addEventListener('click', function () {
  playNext();
});

prevBtn.addEventListener('click', function () {
  playPrev();
});

playAllBtn.addEventListener('click', function () {
  if (!currentFolder) {
    statusText.textContent = 'Primero selecciona una carpeta.';
    return;
  }

  if (isPlayAllMode) {
    stopPlayAll();
  } else {
    startPlayAll();
  }
});

if (addFolderBtn) {
  addFolderBtn.addEventListener('click', startAddFolder);
}

if (renameMediaBtn) {
  renameMediaBtn.addEventListener('click', function () {
    openRenameModal().catch(function (e) {
      statusText.textContent = e.message || 'No se pudo abrir el cambio de nombre.';
    });
  });
}

if (tempPlaylistBtn) {
  tempPlaylistBtn.addEventListener('click', function () {
    openTempPlaylistModal().catch(function (e) {
      statusText.textContent = e.message || 'No se pudo abrir el creador de playlist.';
    });
  });
}

if (viewPlaylistsBtn) {
  viewPlaylistsBtn.addEventListener('click', function () {
    openPlaylistsModal().catch(function (e) {
      statusText.textContent = e.message || 'No se pudieron abrir las playlists.';
    });
  });
}

if (downloadVideoBtn) {
  downloadVideoBtn.addEventListener('click', openDownloadModal);
}

if (downloadStartBtn) {
  downloadStartBtn.addEventListener('click', startDownload);
}

if (downloadTypeSelect) {
  downloadTypeSelect.addEventListener('change', function () {
    syncDownloadTypeControls(true);
  });
}

if (downloadUrlInput) {
  downloadUrlInput.addEventListener('input', function () {
    syncDownloadTypeControls(false);
  });
}

if (downloadFolderSelect) {
  downloadFolderSelect.addEventListener('change', updateDownloadStartButtonState);
}

if (downloadCancelBtn) {
  downloadCancelBtn.addEventListener('click', cancelActiveDownload);
}

if (downloadModal) {
  downloadModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') e.preventDefault();
  });
}

if (downloadHelpBtn) {
  downloadHelpBtn.addEventListener('click', openDownloadHelpModal);
}

if (downloadHelpCloseBtn) {
  downloadHelpCloseBtn.addEventListener('click', closeDownloadHelpModal);
}

if (downloadHelpModal) {
  downloadHelpModal.addEventListener('click', function (e) {
    if (e.target === downloadHelpModal) closeDownloadHelpModal();
  });

  downloadHelpModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDownloadHelpModal();
  });
}

if (folderAccessChooseBtn) {
  folderAccessChooseBtn.addEventListener('click', function () {
    const folderPath = folderPathInput ? folderPathInput.value.trim() : '';

    if (!folderPath) {
      if (folderPathInput) {
        folderPathInput.classList.add('field-error');
        folderPathInput.focus();
      }
      showAutoNotice('Pega la ruta de la carpeta.', 'warning');
      return;
    }

    closeFolderAccessModal(folderPath);
  });
}

if (folderAccessCancelBtn) {
  folderAccessCancelBtn.addEventListener('click', function () {
    closeFolderAccessModal('');
  });
}

if (folderAccessModal) {
  folderAccessModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      closeFolderAccessModal('');
    }

    if (e.key === 'Enter' && e.target === folderPathInput) {
      e.preventDefault();
      if (folderAccessChooseBtn) folderAccessChooseBtn.click();
    }
  });
}

if (folderPathInput) {
  folderPathInput.addEventListener('input', function () {
    folderPathInput.classList.remove('field-error');
  });
}

if (renameFolderSelect) {
  renameFolderSelect.addEventListener('change', refreshRenameFileList);
}

if (renameTypeSelect) {
  renameTypeSelect.addEventListener('change', refreshRenameFileList);
}

if (renameFileSelect) {
  renameFileSelect.addEventListener('change', syncRenameInputFromSelection);
}

if (renameFilePickerBtn) {
  renameFilePickerBtn.addEventListener('click', openRenameFilePicker);
}

if (renameFilePickerCloseBtn) {
  renameFilePickerCloseBtn.addEventListener('click', closeRenameFilePicker);
}

if (renameFilePickerSearch) {
  renameFilePickerSearch.addEventListener('input', function () {
    renderRenameFilePickerList(renameFilePickerSearch.value);
  });
}

if (renameFilePickerList) {
  renameFilePickerList.addEventListener('click', function (e) {
    const row = e.target.closest('.file-picker-row');
    if (!row) return;
    selectRenameFile(Number(row.dataset.index));
  });
}

if (confirmModalOkBtn) {
  confirmModalOkBtn.addEventListener('click', function () { closeConfirmDialog(true); });
}

if (confirmModalCancelBtn) {
  confirmModalCancelBtn.addEventListener('click', function () { closeConfirmDialog(false); });
}

if (confirmModal) {
  confirmModal.addEventListener('click', function (e) {
    if (e.target === confirmModal) closeConfirmDialog(false);
  });

  confirmModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      e.stopPropagation();
      closeConfirmDialog(false);
    }
  });
}

if (renameFilePickerModal) {
  renameFilePickerModal.addEventListener('click', function (e) {
    if (e.target === renameFilePickerModal) closeRenameFilePicker();
  });

  renameFilePickerModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      e.stopPropagation();
      closeRenameFilePicker();
    }
  });
}

if (renameCancelBtn) {
  renameCancelBtn.addEventListener('click', closeRenameModal);
}

if (renameSaveBtn) {
  renameSaveBtn.addEventListener('click', function () {
    saveRenameMedia().catch(function (e) {
      setUtilityStatus(renameStatus, e.message || 'No se pudo cambiar el nombre.', 'error');
    });
  });
}

if (renameModal) {
  renameModal.addEventListener('click', function (e) {
    if (e.target === renameModal) closeRenameModal();
  });

  renameModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeRenameModal();
    if (e.key === 'Enter' && e.target === renameNameInput) {
      e.preventDefault();
      if (renameSaveBtn) renameSaveBtn.click();
    }
  });
}

if (tempPlaylistFolderSelect) {
  tempPlaylistFolderSelect.addEventListener('change', renderTempPlaylistList);
}

if (tempPlaylistSearchInput) {
  tempPlaylistSearchInput.addEventListener('input', renderTempPlaylistList);
}

if (tempPlaylistCancelBtn) {
  tempPlaylistCancelBtn.addEventListener('click', closeTempPlaylistModal);
}

if (tempPlaylistClearBtn) {
  tempPlaylistClearBtn.addEventListener('click', clearTempPlaylist);
}

if (tempPlaylistSaveBtn) {
  tempPlaylistSaveBtn.addEventListener('click', function () {
    saveTempPlaylist().catch(function (e) {
      setUtilityStatus(tempPlaylistStatus, e.message || 'No se pudo guardar la playlist.', 'error');
    });
  });
}

if (playlistNameCancelBtn) {
  playlistNameCancelBtn.addEventListener('click', closePlaylistNameModal);
}

if (playlistNameSaveBtn) {
  playlistNameSaveBtn.addEventListener('click', function () {
    saveNamedPlaylist().catch(function (e) {
      setUtilityStatus(playlistNameStatus, e.message || 'No se pudo guardar la playlist.', 'error');
    });
  });
}

if (playlistNameModal) {
  playlistNameModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePlaylistNameModal();
    if (e.key === 'Enter' && e.target === playlistNameInput) {
      e.preventDefault();
      if (playlistNameSaveBtn) playlistNameSaveBtn.click();
    }
  });
}

if (tempPlaylistModal) {
  tempPlaylistModal.addEventListener('click', function (e) {
    if (e.target === tempPlaylistModal) closeTempPlaylistModal();
  });

  tempPlaylistModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeTempPlaylistModal();
  });
}

if (playlistsCloseBtn) {
  playlistsCloseBtn.addEventListener('click', closePlaylistsModal);
}

if (playlistsModal) {
  playlistsModal.addEventListener('click', function (e) {
    if (e.target === playlistsModal) closePlaylistsModal();
  });

  playlistsModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePlaylistsModal();
  });
}

if (playlistDetailCloseBtn) {
  playlistDetailCloseBtn.addEventListener('click', closePlaylistDetailModal);
}

if (playlistDeleteBtn) {
  playlistDeleteBtn.addEventListener('click', function () {
    deleteActivePlaylist().catch(function (e) {
      showAutoNotice(e.message || 'No se pudo eliminar la playlist.', 'error');
    });
  });
}

if (playlistDetailModal) {
  playlistDetailModal.addEventListener('click', function (e) {
    if (e.target === playlistDetailModal) closePlaylistDetailModal();
  });

  playlistDetailModal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePlaylistDetailModal();
  });
}

progressBar.addEventListener('input', function () {
  if (isLocalAudioActive()) {
    localAudioPlayer.currentTime = Number(progressBar.value) || 0;
    syncLocalController();
    return;
  }

  if (activePlayerKind === 'native') {
    queueNativeSeek(Number(progressBar.value) || 0, false);
    return;
  }

  sendCommandToPlayer({
    type: 'seek',
    sid: activePlayerSid,
    time: Number(progressBar.value) || 0
  });
});

progressBar.addEventListener('change', function () {
  if (activePlayerKind === 'native' && playbackMode === 'external') {
    queueNativeSeek(Number(progressBar.value) || 0, true);
  }
});

localAudioPlayer.addEventListener('loadedmetadata', syncLocalController);
localAudioPlayer.addEventListener('timeupdate', syncLocalController);
localAudioPlayer.addEventListener('play', syncLocalController);
localAudioPlayer.addEventListener('pause', syncLocalController);
localAudioPlayer.addEventListener('ended', handlePlaybackEnded);

if (imageViewerOverlay) {
  imageViewerOverlay.addEventListener('click', function () {
    closeInlineImageViewer();
  });
}

document.addEventListener('keydown', function (e) {
  if ((playbackMode === 'image' || playbackMode === 'image-inline') && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) {
    e.preventDefault();
    if (e.key === 'ArrowRight') {
      playNext();
    } else {
      playPrev();
    }
    return;
  }

  if (e.key === 'Escape' && playbackMode === 'image-inline') {
    e.preventDefault();
    closeInlineImageViewer();
    return;
  }

  if (e.key === 'Escape' && playbackMode === 'image') {
    e.preventDefault();
    closePlayerWindow();
    hideController();
    setIdleStatus();
  }
});

window.addEventListener('pagehide', closePlaybackOnPageExit);
window.addEventListener('beforeunload', closePlaybackOnPageExit);

function handlePlaybackEnded() {
  if (isPlayAllMode) {
    statusText.textContent = 'Video finalizado. Reproduciendo siguiente...';
    setTimeout(playNext, 120);
    return;
  }

  statusText.textContent = 'Video finalizado. Cerrando reproduccion...';

  if (isLocalAudioActive()) {
    stopLocalAudio();
  } else {
    closePlayerWindow();
  }

  hideController();
  if (returnToPlaylistDetail()) return;

  setTimeout(setIdleStatus, 700);
}

// ─── Mensajes del player ──────────────────────────────────────────────────────

channel.onmessage = function (event) {
  const data = event.data || {};

  if (data.type === 'player_ready') {
    if (data.sid && data.sid !== activePlayerSid) return;

    playerAlive = true;
    playerLaunching = false;
    stopPlayerReadyWatchdog();
    statusText.textContent = 'Player activo ✓';

    positionPlayerWindow('player_ready');

    if (pendingPlayerItem) {
      const item = pendingPlayerItem;
      pendingPlayerItem = null;

      setTimeout(function () {
        sendMediaToPlayer(item);
      }, 180);
    }

    return;
  }

  if (data.type === 'player_closed') {
    if (!data.sid || data.sid === activePlayerSid) {
      stopPlayerReadyWatchdog();
      resetNativeSessionState();
      hideController();
      setIdleStatus();
    }
    return;
  }

  if (data.type === 'loaded') {
    if (data.sid && activePlayerSid && data.sid !== activePlayerSid) return;
    playerAlive = true;
    playerLaunching = false;
    stopPlayerReadyWatchdog();
    songTitle.textContent = data.title || songTitle.textContent;
    durationEl.textContent = formatTime(data.duration);
    progressBar.max = Number(data.duration) || 0;
    return;
  }

  if (data.type === 'load_error') {
    if (data.sid && activePlayerSid && data.sid !== activePlayerSid) return;
    statusText.textContent = data.message || 'No se pudo cargar el video en el player.';
    return;
  }

  if (data.type === 'time') {
    if (data.sid && activePlayerSid && data.sid !== activePlayerSid) return;
    playerAlive = true;
    playerLaunching = false;
    stopPlayerReadyWatchdog();
    currentTimeEl.textContent = formatTime(data.current);
    durationEl.textContent = formatTime(data.duration);
    progressBar.max = Number(data.duration) || 0;
    progressBar.value = Number(data.current) || 0;
    setPlayPauseButton(Boolean(data.paused), false);
    return;
  }

  if (data.type === 'ended') {
    if (data.sid && activePlayerSid && data.sid !== activePlayerSid) return;
    handlePlaybackEnded();
    return;
  }
};

// ─── Arranque ─────────────────────────────────────────────────────────────────

updateMediaTypeUi();
// Camino critico primero: limpiar carpetas de la URL, cargar carpetas y pintar
// la libreria. La deteccion de pantallas (que arranca PowerShell en el servidor)
// se difiere para que no compita con el primer render en equipos de gama baja;
// el desplegable de monitores se rellena un instante despues, sin cambiar nada.
function startScreenDetectionDeferred() {
  var run = function () { renderMonitorOptions(); };
  if (typeof window.requestIdleCallback === 'function') {
    window.requestIdleCallback(run, { timeout: 1500 });
  } else {
    setTimeout(run, 0);
  }
}

resetConfiguredFoldersFromUrl()
  .then(function (wasReset) {
    if (wasReset) return;
    loadFolders();
    renderLibrary();
  })
  .catch(function (e) {
    console.warn('[Carpetas] No se pudo ejecutar la limpieza inicial:', e.message);
    loadFolders();
    renderLibrary();
  })
  .finally(startScreenDetectionDeferred);

// Pre-calentar el reproductor nativo poco después del arranque (fuera del
// camino crítico): el primer video se abre casi al instante.
schedulePrewarmNativePlayer(1000);

// ─── Temas (selector de paleta) ───────────────────────────────
// Etapa 1: solo vista previa visual. "Aplicar" aún no persiste el tema;
// se activa cuando el usuario apruebe las paletas.
const APP_THEMES = [
  {
    id: 'santuario',
    name: 'Santuario',
    desc: 'Crema, dorado y verde bosque (actual).',
    swatches: ['#f4efe6', '#ffffff', '#1f6f54', '#b07d24', '#221c14'],
  },
  {
    id: 'cielo',
    name: 'Cielo',
    desc: 'Azul cielo frío y sereno.',
    swatches: ['#e4edf6', '#f2f7fc', '#1f6bb0', '#4d84b0', '#14293e'],
  },
  {
    id: 'oliva',
    name: 'Oliva',
    desc: 'Verde salvia con bronce.',
    swatches: ['#e8ecdb', '#f2f5e7', '#45772f', '#8c8a38', '#232c14'],
  },
  {
    id: 'vino',
    name: 'Vino',
    desc: 'Malva rosado con burdeos.',
    swatches: ['#f2e2e6', '#fbf1f4', '#93203f', '#b06578', '#351320'],
  },
  {
    id: 'noche',
    name: 'Noche',
    desc: 'Oscuro frío con plata y azul luna.',
    swatches: ['#14161c', '#20242e', '#6f9ff0', '#b7c1d3', '#e9edf4'],
  },
  {
    id: 'trigo',
    name: 'Trigo',
    desc: 'Miel ámbar cálido con terracota.',
    swatches: ['#f7e6c8', '#fdf2dc', '#c15a1b', '#d1941a', '#38270c'],
  },
  {
    id: 'indigo',
    name: 'Índigo',
    desc: 'Violeta lavanda con lila.',
    swatches: ['#ece5f5', '#f5f0fb', '#6a34b8', '#8f7dc0', '#261a3a'],
  },
];
const APP_THEME_DEFAULT = 'santuario';
const APP_THEME_STORAGE_KEY = 'cm_theme';

const themeModal = document.getElementById('themeModal');
const themeBtn = document.getElementById('themeBtn');
const themeCloseBtn = document.getElementById('themeCloseBtn');
const themeCancelBtn = document.getElementById('themeCancelBtn');
const themeApplyBtn = document.getElementById('themeApplyBtn');
const themeList = document.getElementById('themeList');
const themeStatus = document.getElementById('themeStatus');

function isKnownThemeId(id) {
  return APP_THEMES.some(function (t) { return t.id === id; });
}

function readStoredThemeId() {
  try {
    const v = localStorage.getItem(APP_THEME_STORAGE_KEY);
    return isKnownThemeId(v) ? v : APP_THEME_DEFAULT;
  } catch (e) {
    return APP_THEME_DEFAULT;
  }
}

// Aplica el tema al documento (santuario = sin atributo, usa :root)
function applyThemeToDocument(id) {
  const root = document.documentElement;
  if (!isKnownThemeId(id) || id === APP_THEME_DEFAULT) {
    root.removeAttribute('data-theme');
  } else {
    root.setAttribute('data-theme', id);
  }
}

// Tema activo: se lee de storage. El <head> ya lo aplicó antes de pintar
// (script inline anti-parpadeo); aquí solo sincronizamos el estado.
let themeActiveId = readStoredThemeId();
let themeSelectedId = themeActiveId;
applyThemeToDocument(themeActiveId);

function renderThemeList() {
  if (!themeList) return;
  themeList.innerHTML = '';
  APP_THEMES.forEach(function (theme) {
    const row = document.createElement('button');
    row.type = 'button';
    row.className = 'theme-row';
    row.setAttribute('role', 'radio');
    row.dataset.themeId = theme.id;
    const isSelected = theme.id === themeSelectedId;
    const isActive = theme.id === themeActiveId;
    row.setAttribute('aria-checked', isSelected ? 'true' : 'false');
    if (isSelected) row.classList.add('is-selected');

    const dots = theme.swatches
      .map(function (c) {
        return '<span class="theme-dot" style="background:' + c + '"></span>';
      })
      .join('');

    row.innerHTML =
      '<span class="theme-swatches">' + dots + '</span>' +
      '<span class="theme-info">' +
        '<span class="theme-name">' + theme.name +
          (isActive ? '<span class="theme-current">Actual</span>' : '') +
        '</span>' +
        '<span class="theme-desc">' + theme.desc + '</span>' +
      '</span>' +
      '<span class="theme-check" aria-hidden="true">✓</span>';

    row.addEventListener('click', function () {
      // Seleccionar = aplicar y guardar al instante. Se queda pase lo que pase
      // al cerrar (X, fuera, o "Listo"). Solo "Cancelar" deshace.
      selectAndPersistTheme(theme.id);
      renderThemeList();
    });
    themeList.appendChild(row);
  });
}

// Tema que estaba activo al abrir el modal (para que "Cancelar" pueda deshacer)
let themeOpeningId = APP_THEME_DEFAULT;

function selectAndPersistTheme(id) {
  themeSelectedId = id;
  themeActiveId = id;
  applyThemeToDocument(id);
  try { localStorage.setItem(APP_THEME_STORAGE_KEY, id); } catch (e) {}
}

function openThemeModal() {
  if (!themeModal) return;
  themeOpeningId = themeActiveId;
  themeSelectedId = themeActiveId;
  if (themeStatus) themeStatus.textContent = '';
  renderThemeList();
  themeModal.classList.add('active');
  themeModal.setAttribute('aria-hidden', 'false');
}

// Cierra el modal. El tema ya quedó aplicado/guardado al seleccionarlo,
// así que cerrar NO revierte.
function closeThemeModal() {
  if (!themeModal) return;
  themeModal.classList.remove('active');
  themeModal.setAttribute('aria-hidden', 'true');
}

if (themeBtn) themeBtn.addEventListener('click', openThemeModal);
if (themeCloseBtn) themeCloseBtn.addEventListener('click', closeThemeModal);
if (themeModal) {
  themeModal.addEventListener('click', function (e) {
    if (e.target === themeModal) closeThemeModal();
  });
}
// "Cancelar" = deshacer: vuelve al tema que había al abrir el modal.
if (themeCancelBtn) {
  themeCancelBtn.addEventListener('click', function () {
    selectAndPersistTheme(themeOpeningId);
    closeThemeModal();
  });
}
// "Listo" = confirmar y cerrar (ya está aplicado; solo avisa).
if (themeApplyBtn) {
  themeApplyBtn.addEventListener('click', function () {
    const t = APP_THEMES.find(function (x) { return x.id === themeActiveId; });
    closeThemeModal();
    if (typeof showAutoNotice === 'function') {
      showAutoNotice('Tema "' + (t ? t.name : themeActiveId) + '" aplicado.', 'success');
    }
  });
}

// ─── Dropdown propio para <select> (multiplataforma, look temado) ──────
// El <select> nativo cerrado ya se ve perfecto en todos los contextos.
// Interceptamos solo la APERTURA para mostrar un panel propio (redondeado,
// temado, idéntico en Windows/Mac/Linux). El <select> sigue siendo la
// fuente de verdad, así que la lógica existente (value/change) no cambia.
// El panel se monta en <body> (portal) con coordenadas calculadas en el
// espacio del lienzo escalado, para que ningún overflow de un modal/tarjeta
// lo recorte.
(function () {
  var openSelect = null;
  var panel = document.createElement('div');
  panel.className = 'cm-select-panel';
  panel.setAttribute('role', 'listbox');

  function scale() {
    var s = window.innerWidth / (typeof APP_DESIGN_WIDTH === 'number' ? APP_DESIGN_WIDTH : 1389);
    return (s && isFinite(s)) ? s : 1;
  }

  function closeOpen() {
    if (!openSelect) return;
    openSelect = null;
    panel.classList.remove('is-open');
    panel.innerHTML = '';
    if (panel.parentNode) panel.parentNode.removeChild(panel);
    document.removeEventListener('mousedown', onDocDown, true);
    document.removeEventListener('keydown', onKeyDown, true);
    window.removeEventListener('resize', closeOpen);
    window.removeEventListener('scroll', onScroll, true);
  }

  function onDocDown(e) {
    if (openSelect && e.target !== openSelect && !panel.contains(e.target)) closeOpen();
  }

  // Cierra al hacer scroll FUERA del panel (igual que un select nativo);
  // ignora el scroll interno de la propia lista de opciones.
  function onScroll(e) {
    if (e.target === panel || (e.target && e.target.nodeType === 1 && panel.contains(e.target))) return;
    closeOpen();
  }

  function items() {
    return Array.prototype.slice.call(panel.querySelectorAll('.cm-select-option'));
  }

  function setActive(idx) {
    var list = items();
    list.forEach(function (it) { it.classList.remove('is-active'); });
    if (idx >= 0 && idx < list.length) {
      list[idx].classList.add('is-active');
      list[idx].scrollIntoView({ block: 'nearest' });
    }
  }

  function onKeyDown(e) {
    if (!openSelect) return;
    var list = items();
    var cur = list.findIndex(function (it) { return it.classList.contains('is-active'); });
    function moveTo(next) {
      var dir = next > cur ? 1 : -1;
      while (next >= 0 && next < list.length && list[next].classList.contains('is-disabled')) next += dir;
      if (next >= 0 && next < list.length) setActive(next);
    }
    if (e.key === 'ArrowDown') { e.preventDefault(); moveTo(cur < 0 ? 0 : cur + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); moveTo(cur < 0 ? list.length - 1 : cur - 1); }
    else if (e.key === 'Home') { e.preventDefault(); moveTo(0); }
    else if (e.key === 'End') { e.preventDefault(); moveTo(list.length - 1); }
    else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (cur >= 0 && list[cur] && !list[cur].classList.contains('is-disabled')) list[cur].click();
    } else if (e.key === 'Escape' || e.key === 'Tab') {
      closeOpen();
      if (e.key === 'Escape') openSelect && openSelect.focus();
    }
  }

  function buildPanel(select) {
    panel.innerHTML = '';
    Array.prototype.forEach.call(select.options, function (opt, i) {
      var item = document.createElement('div');
      item.className = 'cm-select-option';
      item.setAttribute('role', 'option');
      item.textContent = opt.textContent;
      if (opt.disabled) item.classList.add('is-disabled');
      if (i === select.selectedIndex) {
        item.classList.add('is-selected', 'is-active');
        item.setAttribute('aria-selected', 'true');
      }
      item.addEventListener('click', function () {
        if (opt.disabled) return;
        if (select.selectedIndex !== i) {
          select.selectedIndex = i;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeOpen();
        select.focus();
      });
      panel.appendChild(item);
    });
  }

  function positionPanel(select) {
    var s = scale();
    var r = select.getBoundingClientRect();       // px reales (post-escala)
    var left = r.left / s;                         // a espacio del lienzo
    var width = r.width / s;
    var below = r.bottom / s + 6;
    panel.style.left = left + 'px';
    panel.style.width = width + 'px';
    panel.style.top = below + 'px';
    // ¿cabe abajo? si no y hay más espacio arriba, abre hacia arriba
    var viewH = window.innerHeight / s;
    var spaceBelow = viewH - r.bottom / s;
    var spaceAbove = r.top / s;
    var h = panel.offsetHeight;
    if (h + 8 > spaceBelow && spaceAbove > spaceBelow) {
      panel.style.top = (r.top / s - h - 6) + 'px';
    }
  }

  function openFor(select) {
    if (openSelect === select) { closeOpen(); return; }
    closeOpen();
    if (select.disabled) return;
    buildPanel(select);
    document.body.appendChild(panel);
    panel.classList.add('is-open');
    openSelect = select;
    positionPanel(select);
    var active = panel.querySelector('.cm-select-option.is-active');
    if (active) active.scrollIntoView({ block: 'nearest' });
    document.addEventListener('mousedown', onDocDown, true);
    document.addEventListener('keydown', onKeyDown, true);
    window.addEventListener('resize', closeOpen);
    window.addEventListener('scroll', onScroll, true);
  }

  function enhanceSelect(select) {
    if (select.__cmSelect) return;
    if (select.id === 'renameFileSelect' || select.classList.contains('sr-only')) return;
    select.__cmSelect = true;
    select.addEventListener('mousedown', function (e) {
      e.preventDefault();
      select.focus();
      openFor(select);
    });
    select.addEventListener('keydown', function (e) {
      if (openSelect === select) return;
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        openFor(select);
      }
    });
  }

  function enhanceAllSelects() {
    Array.prototype.forEach.call(document.querySelectorAll('select'), enhanceSelect);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enhanceAllSelects);
  } else {
    enhanceAllSelects();
  }
})();

/* Marquee de statusText: si el mensaje no cabe en una línea, se desliza en
   bucle suave (dos copias + separación) para poder leerlo completo. Muchas
   partes del código escriben statusText.textContent directo, así que un
   observador reconstruye el marquee cada vez que cambia el texto. */
(function setupStatusMarquee() {
  var el = document.getElementById('statusText');
  if (!el) return;
  var SPEED = 45; // px por segundo: ritmo de lectura cómodo
  var HOLD_MS = 8000; // cuánto vive un mensaje corto antes de ocultarse
  var raf = 0;
  var obs = null;
  var hideTimer = 0;
  // Texto lógico actual. Se actualiza SOLO cuando código externo cambia el
  // statusText (el observador se desconecta mientras nosotros montamos el
  // marquee, así que un disparo del observador = cambio externo real).
  var logicalText = el.textContent;

  function build() {
    if (obs) obs.disconnect();
    clearTimeout(hideTimer);
    // Partir de texto plano para medir el ancho de una línea.
    el.classList.remove('is-marquee');
    el.textContent = logicalText;

    var holdMs = HOLD_MS;
    if (logicalText && el.scrollWidth > el.clientWidth + 1) {
      // Desborda: construir pista con dos copias para el bucle continuo.
      el.classList.add('is-marquee');
      el.textContent = '';
      var track = document.createElement('span');
      track.className = 'status-mq';
      var a = document.createElement('span');
      a.textContent = logicalText;
      var b = document.createElement('span');
      b.textContent = logicalText;
      b.setAttribute('aria-hidden', 'true');
      track.appendChild(a);
      track.appendChild(b);
      el.appendChild(track);
      // Duración proporcional al ancho => velocidad constante y legible.
      var half = a.offsetWidth; // incluye la separación (padding-right)
      var dur = Math.max(6, half / SPEED);
      track.style.animationDuration = dur.toFixed(2) + 's';
      // Mensajes largos viven lo suficiente para leerse ~2 vueltas completas.
      holdMs = Math.max(HOLD_MS, dur * 2 * 1000);
    }

    // El mensaje se muestra un rato y luego se quita solo: la interacción ya
    // pasó. Un mensaje nuevo reinicia el temporizador.
    if (logicalText) {
      hideTimer = setTimeout(function () {
        logicalText = '';
        scheduleBuild();
      }, holdMs);
    }

    if (obs) obs.observe(el, { childList: true, characterData: true, subtree: true });
  }

  function scheduleBuild() {
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(build);
  }

  obs = new MutationObserver(function () {
    // El observador solo dispara por cambios externos (nosotros nos
    // desconectamos al montar). Ahí el texto nuevo es el textContent actual.
    logicalText = el.textContent;
    scheduleBuild();
  });
  // Al redimensionar re-evaluamos con el mismo texto lógico (sin leer el DOM,
  // que en modo marquee tiene el texto duplicado).
  window.addEventListener('resize', scheduleBuild);
  scheduleBuild();
})();
