<?php
// recover-diary.php - Recupera il diario dal backup più recente

$backups = glob('backups/diary_*.json');
if (empty($backups)) {
    die("Nessun backup trovato!");
}

// Ordina per data più recente
rsort($backups);
$latestBackup = $backups[0];

echo "<h1>Recupero Diario</h1>";
echo "<p>Backup più recente: " . basename($latestBackup) . "</p>";
echo "<p>Dimensione: " . filesize($latestBackup) . " bytes</p>";

if (isset($_GET['confirm'])) {
    // Fai backup del file corrente
    if (file_exists('diary-data.json')) {
        copy('diary-data.json', 'diary-data-corrupted-' . time() . '.json');
    }
    
    // Ripristina dal backup
    if (copy($latestBackup, 'diary-data.json')) {
        echo "<p style='color: green;'>✓ Diario recuperato con successo!</p>";
        echo "<p><a href='diario.php'>Torna al diario</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Errore durante il recupero!</p>";
    }
} else {
    echo "<p><a href='?confirm=1' onclick='return confirm(\"Sei sicuro?\")'>Conferma recupero</a></p>";
}
?>