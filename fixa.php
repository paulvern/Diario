<?php
// fix-permissions.php - Sistema i permessi dei file

echo "<h1>Fix Permessi File</h1><pre>";

// File e cartelle da controllare
$items = [
    'diary-data.json' => '0666',
    'uploads/' => '0777',
    'backups/' => '0777'
];

foreach ($items as $item => $permission) {
    echo "Controllo $item...\n";
    
    if (!file_exists($item)) {
        if (substr($item, -1) === '/') {
            // È una cartella
            if (mkdir($item, octdec($permission), true)) {
                echo "  ✓ Cartella creata\n";
            } else {
                echo "  ❌ Errore creazione cartella\n";
            }
        } else {
            // È un file
            if (file_put_contents($item, '{}') !== false) {
                echo "  ✓ File creato\n";
            } else {
                echo "  ❌ Errore creazione file\n";
            }
        }
    }
    
    // Imposta permessi
    if (chmod($item, octdec($permission))) {
        echo "  ✓ Permessi impostati a $permission\n";
    } else {
        echo "  ❌ Errore impostazione permessi\n";
    }
}

echo "</pre>";
echo "<p><a href='diario.php'>Torna al diario</a></p>";
?>