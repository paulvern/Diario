<?php
// install.php - Script per creare la struttura iniziale
$dirs = ['backups'];
$files = ['diary-data.json' => '{}'];

echo "<h2>Installazione Diario di Viaggio</h2>";

// Crea directory
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "✓ Directory '$dir' creata<br>";
        } else {
            echo "✗ Errore creazione directory '$dir'<br>";
        }
    } else {
        echo "• Directory '$dir' già esistente<br>";
    }
}

// Crea file
foreach ($files as $file => $content) {
    if (!file_exists($file)) {
        if (file_put_contents($file, $content) !== false) {
            echo "✓ File '$file' creato<br>";
        } else {
            echo "✗ Errore creazione file '$file'<br>";
        }
    } else {
        echo "• File '$file' già esistente<br>";
    }
}

// Verifica permessi
echo "<h3>Verifica permessi:</h3>";
$checkPaths = ['diary-data.json', 'backups'];
foreach ($checkPaths as $path) {
    if (is_writable($path)) {
        echo "✓ '$path' è scrivibile<br>";
    } else {
        echo "✗ '$path' NON è scrivibile - correggere i permessi!<br>";
    }
}

echo "<br><strong>Installazione completata!</strong><br>";
echo "<a href='diario.html'>Vai al diario</a>";
?>