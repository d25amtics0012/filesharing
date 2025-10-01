<?php
require_once 'vendor/autoload.php';
require_once 'SupabaseClient.php';

use chillerlan\QRCode\QRCode;

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$supabase = new SupabaseClient();
$message = '';
$messageType = '';

$maxFileSize = 10 * 1024 * 1024;
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain', 'application/zip', 'application/x-zip-compressed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['file_id'])) {
        try {
            $fileId = filter_var($_POST['file_id'], FILTER_VALIDATE_INT);
            if ($fileId === false) {
                throw new Exception('Invalid file ID');
            }
            
            $fileRecord = $supabase->getFileById($fileId);
            if (!$fileRecord) {
                throw new Exception('File not found');
            }
            
            $filename = basename(parse_url($fileRecord['public_url'], PHP_URL_PATH));
            
            $supabase->deleteFile($filename);
            $supabase->deleteFileRecord($fileId);
            $message = 'File deleted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting file: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        try {
            $tmpPath = $_FILES['file']['tmp_name'];
            $originalName = basename($_FILES['file']['name']);
            $fileSize = $_FILES['file']['size'];
            $mimeType = mime_content_type($tmpPath);
            
            if ($fileSize > $maxFileSize) {
                throw new Exception('File size exceeds 10MB limit');
            }
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('File type not allowed. Allowed: images, PDF, text, and ZIP files');
            }
            
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            
            $uploadResult = $supabase->uploadFile($tmpPath, $uniqueName);
            $publicUrl = $supabase->getPublicUrl($uniqueName);
            $supabase->insertFileRecord($originalName, $fileSize, $publicUrl);
            
            $message = 'File uploaded successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error uploading file: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

try {
    $files = $supabase->getFiles();
} catch (Exception $e) {
    $files = [];
    if (empty($message)) {
        $message = 'Error loading files: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function generateQRCode($url) {
    $qrcode = new QRCode();
    return $qrcode->render($url);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Sharing - Supabase Storage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📁 File Sharing</h1>
            <p>Upload, share, and manage your files with QR codes</p>
        </header>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="upload-section">
            <h2>Upload New File</h2>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="file-input-wrapper">
                    <input type="file" name="file" id="file" required>
                    <label for="file" class="file-label">
                        <span class="file-icon">📎</span>
                        <span class="file-text">Choose a file or drag it here</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Upload File</button>
            </form>
            <p class="upload-info">Max file size: 10MB. Allowed: images, PDF, text, ZIP files</p>
        </section>

        <section class="files-section">
            <h2>Uploaded Files</h2>
            <?php if (empty($files)): ?>
                <p class="no-files">No files uploaded yet. Upload your first file above!</p>
            <?php else: ?>
                <div class="files-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card">
                            <div class="file-header">
                                <h3 class="file-name"><?php echo htmlspecialchars($file['filename']); ?></h3>
                                <div class="file-meta">
                                    <span class="file-size"><?php echo formatFileSize($file['file_size']); ?></span>
                                    <span class="file-date"><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="qr-code">
                                <img src="<?php echo generateQRCode($file['public_url']); ?>" alt="QR Code">
                                <p class="qr-text">Scan to download</p>
                            </div>
                            
                            <div class="file-actions">
                                <a href="<?php echo htmlspecialchars($file['public_url']); ?>" target="_blank" class="btn btn-secondary">
                                    ⬇️ Download
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?');">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                            
                            <div class="file-url">
                                <input type="text" value="<?php echo htmlspecialchars($file['public_url']); ?>" readonly onclick="this.select();">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        const fileInput = document.getElementById('file');
        const fileLabel = document.querySelector('.file-label');
        const fileText = document.querySelector('.file-text');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileText.textContent = this.files[0].name;
            }
        });

        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        fileLabel.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            fileInput.files = e.dataTransfer.files;
            if (fileInput.files.length > 0) {
                fileText.textContent = fileInput.files[0].name;
            }
        });
    </script>
</body>
</html>
