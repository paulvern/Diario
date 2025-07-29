<?php
// check-diary.php - Diagnostica del sistema diario

echo "<h1>Diagnostica Sistema Diario</h1>";
echo "<pre>";

// 1. Controlla file diary-data.json
echo "=== FILE DIARY-DATA.JSON ===\n";
if (file_exists('diary-data.json')) {
    $size = filesize('diary-data.json');
    echo "✓ File esiste\n";
    echo "  Dimensione: $size bytes\n";
    echo "  Permessi: " . substr(sprintf('%o', fileperms('diary-data.json')), -4) . "\n";
    echo "  Ultima modifica: " . date('Y-m-d H:i:s', filemtime('diary-data.json')) . "\n";
    
    $content = file_get_contents('diary-data.json');
    echo "  Contenuto (primi 500 caratteri):\n";
    echo "  " . substr($content, 0, 500) . "\n";
    
    $json = json_decode($content, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "  ❌ ERRORE JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "  ✓ JSON valido\n";
        echo "  Numero di voci: " . count($json) . "\n";
    }
} else {
    echo "❌ File non esiste!\n";
}

// 2. Controlla cartella uploads
echo "\n=== CARTELLA UPLOADS ===\n";
if (file_exists('uploads/')) {
    echo "✓ Cartella esiste\n";
    echo "  Permessi: " . substr(sprintf('%o', fileperms('uploads/')), -4) . "\n";
    $files = glob('uploads/*');
    echo "  Numero di file: " . count($files) . "\n";
} else {
    echo "❌ Cartella non esiste!\n";
}

// 3. Controlla cartella backups
echo "\n=== CARTELLA BACKUPS ===\n";
if (file_exists('backups/')) {
    echo "✓ Cartella esiste\n";
    echo "  Permessi: " . substr(sprintf('%o', fileperms('backups/')), -4) . "\n";
    $backups = glob('backups/diary_*.json');
    echo "  Numero di backup: " . count($backups) . "\n";
    if (count($backups) > 0) {
        echo "  Ultimi 5 backup:\n";
        rsort($backups);
        foreach (array_slice($backups, 0, 5) as $backup) {
            echo "    - " . basename($backup) . " (" . filesize($backup) . " bytes)\n";
        }
    }
} else {
    echo "❌ Cartella non esiste!\n";
}

// 4. Controlla PHP settings
echo "\n=== PHP SETTINGS ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Max upload size: " . ini_get('upload_max_filesize') . "\n";
echo "Max POST size: " . ini_get('post_max_size') . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";

// 5. Test scrittura
echo "\n=== TEST SCRITTURA ===\n";
$testFile = 'test-write-' . time() . '.txt';
if (file_put_contents($testFile, 'test') !== false) {
    echo "✓ Scrittura file OK\n";
    unlink($testFile);
} else {
    echo "❌ ERRORE: Impossibile scrivere file!\n";
}

// 6. Controlla sessioni
echo "\n=== SESSIONI ===\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session save path: " . session_save_path() . "\n";

echo "</pre>";

// 7. Form di test
?>
<hr>
<h2>Test Salvataggio Manuale</h2>
<form method="post" action="test-save.php">
    <button type="submit">Test Salvataggio Dati</button>
</form>

<h2>Azioni di Recupero</h2>
<p>
    <a href="recover-diary.php" onclick="return confirm('Vuoi recuperare dal backup più recente?')">
        Recupera da Backup
    </a>
</p>