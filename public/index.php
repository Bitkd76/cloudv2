<?php
/**
 * Mon Cloud Familial - Interface de stockage
 * Version corrigée et optimisée
 */

// Configuration
$storage_dir = __DIR__ . '/cloud-storage/';

// Création du dossier de stockage si nécessaire
if (!is_dir($storage_dir)) {
    mkdir($storage_dir, 0755, true); // 0755 plus sécurisé que 0777
}

$message = '';
$message_type = '';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Upload de fichier
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $originalName = basename($file['name']);
        
        // Sécurisation du nom de fichier
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $targetFile = $storage_dir . $safeName;
        
        // Éviter l'écrasement
        if (file_exists($targetFile)) {
            $pathInfo = pathinfo($safeName);
            $counter = 1;
            do {
                $safeName = $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
                $targetFile = $storage_dir . $safeName;
                $counter++;
            } while (file_exists($targetFile));
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $message = "Fichier uploadé avec succès : " . htmlspecialchars($safeName);
            $message_type = 'success';
        } else {
            $message = "Erreur lors de l'upload du fichier.";
            $message_type = 'error';
        }
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = "Erreur lors de l'upload du fichier (code: " . $_FILES['file']['error'] . ")";
        $message_type = 'error';
    }

    // Suppression de fichier
    if (isset($_POST['delete_file']) && !empty($_POST['delete_file'])) {
        $delFile = $storage_dir . basename($_POST['delete_file']);
        
        // Vérification que le fichier est bien dans le dossier de stockage
        $realPath = realpath($delFile);
        $realStorageDir = realpath($storage_dir);
        
        if ($realPath && strpos($realPath, $realStorageDir) === 0) {
            if (unlink($realPath)) {
                $message = "Fichier supprimé : " . htmlspecialchars(basename($_POST['delete_file']));
                $message_type = 'success';
            } else {
                $message = "Erreur lors de la suppression du fichier.";
                $message_type = 'error';
            }
        } else {
            $message = "Fichier non trouvé ou accès non autorisé.";
            $message_type = 'error';
        }
    }

    // Renommage de fichier
    if (isset($_POST['rename_file']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
        $oldName = basename($_POST['old_name']);
        $oldFile = $storage_dir . $oldName;
        
        $ext = pathinfo($oldName, PATHINFO_EXTENSION);
        $newName = trim(preg_replace('/[^a-zA-Z0-9\-_. ]/', '', $_POST['new_name']));
        
        if (!empty($newName)) {
            $newFile = $storage_dir . $newName . '.' . $ext;
            
            // Vérification de sécurité
            $realOldPath = realpath($oldFile);
            $realStorageDir = realpath($storage_dir);
            
            if ($realOldPath && strpos($realOldPath, $realStorageDir) === 0) {
                if (!file_exists($newFile)) {
                    if (rename($realOldPath, $newFile)) {
                        $message = "Fichier renommé en : " . htmlspecialchars($newName . '.' . $ext);
                        $message_type = 'success';
                    } else {
                        $message = "Erreur lors du renommage.";
                        $message_type = 'error';
                    }
                } else {
                    $message = "Erreur : un fichier avec ce nom existe déjà.";
                    $message_type = 'error';
                }
            } else {
                $message = "Fichier non trouvé ou accès non autorisé.";
                $message_type = 'error';
            }
        } else {
            $message = "Le nouveau nom ne peut pas être vide.";
            $message_type = 'error';
        }
    }
    
    // Redirection POST/GET pour éviter la resoumission
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
    }
}

// Récupération du message flash
session_start();
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? '';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Calcul de l'espace disque
$free = disk_free_space($storage_dir);
$total = disk_total_space($storage_dir);
$free_gb = round($free / 1024 / 1024 / 1024, 2);
$total_gb = round($total / 1024 / 1024 / 1024, 2);
$used_percent = round((($total - $free) / $total) * 100, 1);

// Liste des fichiers
$files = array_diff(scandir($storage_dir), ['.', '..']);

// Fonction helper pour déterminer le type de fichier
function getFileType(string $ext): string {
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    $videoExts = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];
    
    if (in_array($ext, $imageExts)) return 'image';
    if (in_array($ext, $videoExts)) return 'video';
    return 'file';
}

// Fonction pour formater la taille des fichiers
function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' Go';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' Ko';
    }
    return $bytes . ' octets';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Cloud Familial</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: rgba(30, 41, 59, 0.5);
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-blue: #0ea5e9;
            --accent-blue-dark: #0284c7;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
            --radius: 0.75rem;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-primary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Header */
        header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 1rem;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            background: linear-gradient(135deg, var(--accent-blue) 0%, #2563eb 100%);
            padding: 0.75rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo svg {
            width: 2rem;
            height: 2rem;
            fill: white;
        }
        
        .header-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .header-text p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Main */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        /* Storage Info */
        .storage-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .storage-icon {
            width: 2.5rem;
            height: 2.5rem;
            fill: var(--accent-blue);
        }
        
        .storage-details {
            flex: 1;
        }
        
        .storage-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .storage-header span:first-child {
            font-weight: 500;
        }
        
        .storage-header span:last-child {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .progress-bar {
            height: 0.75rem;
            background: var(--border-color);
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-blue) 0%, #2563eb 100%);
            border-radius: 0.375rem;
            transition: width 0.3s ease;
        }
        
        /* Message */
        .message {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--accent-green);
        }
        
        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--accent-red);
        }
        
        /* Upload Form */
        .upload-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .file-input-wrapper {
            flex: 1;
            min-width: 200px;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            background: rgba(51, 65, 85, 0.5);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-primary);
            cursor: pointer;
        }
        
        input[type="file"]::file-selector-button {
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            background: var(--accent-blue);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        input[type="file"]::file-selector-button:hover {
            background: var(--accent-blue-dark);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue) 0%, #2563eb 100%);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .btn-outline:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            background: rgba(14, 165, 233, 0.1);
        }
        
        .btn-danger:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
            background: rgba(239, 68, 68, 0.1);
        }
        
        .btn-warning:hover {
            border-color: var(--accent-amber);
            color: var(--accent-amber);
            background: rgba(245, 158, 11, 0.1);
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Progress Upload */
        #progressContainer {
            margin-top: 1rem;
            display: none;
        }
        
        #progressText {
            display: block;
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Files Grid */
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .file-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1rem;
            transition: all 0.2s;
        }
        
        .file-card:hover {
            border-color: rgba(14, 165, 233, 0.5);
            background: var(--bg-secondary);
        }
        
        .file-preview {
            height: 8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .file-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .file-preview video {
            max-width: 100%;
            max-height: 100%;
        }
        
        .file-icon {
            width: 3rem;
            height: 3rem;
        }
        
        .file-icon.image { fill: var(--accent-blue); }
        .file-icon.video { fill: #f43f5e; }
        .file-icon.file { fill: var(--accent-amber); }
        
        .file-name {
            text-align: center;
            margin-bottom: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-name .ext {
            color: var(--text-secondary);
        }
        
        .file-size {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .file-actions .btn {
            flex: 1;
        }
        
        .file-actions .btn-icon {
            flex: 0;
            padding: 0.5rem;
        }
        
        /* Rename Form */
        .rename-form {
            display: none;
            margin-bottom: 0.75rem;
        }
        
        .rename-form.active {
            display: block;
        }
        
        .rename-row {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .rename-input {
            flex: 1;
            padding: 0.5rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        
        .rename-input:focus {
            outline: none;
            border-color: var(--accent-blue);
        }
        
        .rename-ext {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .rename-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-success {
            background: var(--accent-green);
            color: white;
        }
        
        .btn-success:hover {
            opacity: 0.9;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }
        
        .empty-state svg {
            width: 4rem;
            height: 4rem;
            fill: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .header-text h1 {
                font-size: 1.25rem;
            }
            
            .upload-form {
                flex-direction: column;
            }
            
            .file-input-wrapper {
                width: 100%;
            }
            
            .btn-primary {
                width: 100%;
            }
            
            .files-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="logo">
            <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
        </div>
        <div class="header-text">
            <h1>Mon Cloud Familial</h1>
            <p>Stockage sécurisé pour toute la famille</p>
        </div>
    </div>
</header>

<main>
    <!-- Espace disque -->
    <div class="card">
        <div class="card-content">
            <div class="storage-info">
                <svg class="storage-icon" viewBox="0 0 24 24"><path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/></svg>
                <div class="storage-details">
                    <div class="storage-header">
                        <span>Espace disque</span>
                        <span><?php echo "$free_gb Go libres sur $total_gb Go"; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $used_percent; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card">
        <div class="card-content">
            <form id="uploadForm" action="" method="post" enctype="multipart/form-data" class="upload-form">
                <div class="file-input-wrapper">
                    <input type="file" name="file" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4v6zm-4 2h14v2H5v-2z"/></svg>
                    Uploader
                </button>
            </form>
            
            <div id="progressContainer">
                <div class="progress-bar">
                    <div id="progressBar" class="progress-fill" style="width: 0%"></div>
                </div>
                <span id="progressText">0%</span>
            </div>
        </div>
    </div>

    <!-- Files Grid -->
    <?php if (empty($files)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
        <h3>Aucun fichier</h3>
        <p>Uploadez votre premier fichier pour commencer</p>
    </div>
    <?php else: ?>
    <div class="files-grid">
        <?php foreach ($files as $f):
            $nameWithoutExt = pathinfo($f, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $fileType = getFileType($ext);
            $filePath = $storage_dir . $f;
            $fileSize = file_exists($filePath) ? formatFileSize(filesize($filePath)) : '';
        ?>
        <div class="file-card">
            <!-- Preview -->
            <div class="file-preview">
                <?php if ($fileType === 'image'): ?>
                    <img src="cloud-storage/<?php echo rawurlencode($f); ?>" alt="<?php echo htmlspecialchars($f); ?>" loading="lazy">
                <?php elseif ($fileType === 'video'): ?>
                    <video src="cloud-storage/<?php echo rawurlencode($f); ?>" preload="metadata"></video>
                <?php else: ?>
                    <svg class="file-icon file" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <?php endif; ?>
            </div>

            <!-- Nom du fichier (affiché par défaut) -->
            <p class="file-name" title="<?php echo htmlspecialchars($f); ?>">
                <span class="basename"><?php echo htmlspecialchars($nameWithoutExt); ?></span><span class="ext">.<?php echo htmlspecialchars($ext); ?></span>
            </p>
            
            <?php if ($fileSize): ?>
            <p class="file-size"><?php echo $fileSize; ?></p>
            <?php endif; ?>

            <!-- Formulaire de renommage (caché par défaut) -->
            <form action="" method="post" class="rename-form">
                <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($f); ?>">
                <input type="hidden" name="rename_file" value="1">
                <div class="rename-row">
                    <input type="text" name="new_name" class="rename-input" value="<?php echo htmlspecialchars($nameWithoutExt); ?>">
                    <span class="rename-ext">.<?php echo htmlspecialchars($ext); ?></span>
                </div>
                <div class="rename-buttons">
                    <button type="submit" class="btn btn-sm btn-success">✔ Valider</button>
                    <button type="button" class="btn btn-sm btn-outline btn-cancel-rename">✕ Annuler</button>
                </div>
            </form>

            <!-- Actions -->
            <div class="file-actions">
                <form action="" method="post" style="flex: 1; margin: 0;">
                    <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($f); ?>">
                    <button type="submit" class="btn btn-sm btn-outline btn-danger" style="width: 100%;"
                            onclick="return confirm('Supprimer <?php echo htmlspecialchars(addslashes($f)); ?> ?')">
                        🗑
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-outline btn-warning btn-icon btn-rename">✏️</button>
                <a href="cloud-storage/<?php echo rawurlencode($f); ?>" download class="btn btn-sm btn-outline btn-icon">⬇</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<script>
// Upload avec barre de progression
const uploadForm = document.getElementById('uploadForm');
const progressContainer = document.getElementById('progressContainer');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');

uploadForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = uploadForm.querySelector('input[name="file"]');
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);

    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            progressContainer.style.display = 'block';
            progressBar.style.width = pct + '%';
            progressText.textContent = pct + '%';
        }
    };

    xhr.onload = function() {
        if (xhr.status === 200) {
            progressBar.style.width = '100%';
            progressText.textContent = 'Terminé !';
            setTimeout(() => location.reload(), 500);
        } else {
            progressText.textContent = "Erreur lors de l'upload.";
        }
    };

    xhr.onerror = function() {
        progressText.textContent = "Erreur de connexion.";
    };

    xhr.send(formData);
});

// Rename : toggle formulaire
document.querySelectorAll('.btn-rename').forEach(btn => {
    btn.addEventListener('click', function() {
        const card = btn.closest('.file-card');
        const fileName = card.querySelector('.file-name');
        const renameForm = card.querySelector('.rename-form');
        const input = renameForm.querySelector('.rename-input');

        // Fermer tous les autres
        document.querySelectorAll('.file-card').forEach(c => {
            if (c !== card) {
                c.querySelector('.file-name').style.display = '';
                c.querySelector('.rename-form').classList.remove('active');
            }
        });

        const isOpen = renameForm.classList.contains('active');
        fileName.style.display = isOpen ? '' : 'none';
        renameForm.classList.toggle('active');

        if (!isOpen) {
            input.focus();
            input.select();
        }
    });
});

// Cancel rename
document.querySelectorAll('.btn-cancel-rename').forEach(btn => {
    btn.addEventListener('click', function() {
        const card = btn.closest('.file-card');
        card.querySelector('.file-name').style.display = '';
        card.querySelector('.rename-form').classList.remove('active');
    });
});
</script>

</body>
</html>
