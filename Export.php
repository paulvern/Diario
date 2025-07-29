<?php
// export.php - Esporta il diario in formato JSON o HTML

$DIARY_FILE = 'diary-data.json';
$format = $_GET['format'] ?? 'json';

if (!file_exists($DIARY_FILE)) {
    die('Nessun diario trovato');
}

$diaryData = json_decode(file_get_contents($DIARY_FILE), true);

if ($format === 'json') {
    // Download JSON
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="diario-viaggio-' . date('Y-m-d') . '.json"');
    echo json_encode($diaryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($format === 'html') {
    // Genera HTML stampabile
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="diario-viaggio-' . date('Y-m-d') . '.html"');
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il Mio Diario di Viaggio</title>
    <style>
        body {
            font-family: Georgia, serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 { text-align: center; color: #8B4513; }
        .day { 
            margin: 30px 0; 
            padding: 20px; 
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }
        .date { 
            font-size: 20px; 
            font-weight: bold; 
            color: #8B4513; 
            margin-bottom: 15px;
        }
        .period { 
            margin: 15px 0; 
            padding: 10px;
            background: #f9f9f9;
        }
        .period-title { 
            font-weight: bold; 
            color: #666; 
        }
        .photos { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            margin-top: 10px;
        }
        .photos img { 
            max-width: 200px; 
            max-height: 200px;
            object-fit: cover;
        }
        @media print {
            .day { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <h1>Il Mio Diario di Viaggio</h1>
    <?php
    ksort($diaryData);
    foreach ($diaryData as $date => $entry):
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    ?>
    <div class="day">
        <div class="date"><?= $dateObj->format('l, d F Y') ?></div>
        
        <?php if (!empty($entry['morning']['text']) || !empty($entry['morning']['photos'])): ?>
        <div class="period">
            <div class="period-title">🌅 Mattina</div>
            <?= nl2br(htmlspecialchars($entry['morning']['text'] ?? '')) ?>
            <?php if (!empty($entry['morning']['photos'])): ?>
            <div class="photos">
                <?php foreach ($entry['morning']['photos'] as $photo): ?>
                <img src="<?= $photo ?>" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($entry['afternoon']['text']) || !empty($entry['afternoon']['photos'])): ?>
        <div class="period">
            <div class="period-title">☀️ Pomeriggio</div>
            <?= nl2br(htmlspecialchars($entry['afternoon']['text'] ?? '')) ?>
            <?php if (!empty($entry['afternoon']['photos'])): ?>
            <div class="photos">
                <?php foreach ($entry['afternoon']['photos'] as $photo): ?>
                <img src="<?= $photo ?>" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($entry['evening']['text']) || !empty($entry['evening']['photos'])): ?>
        <div class="period">
            <div class="period-title">🌙 Sera</div>
            <?= nl2br(htmlspecialchars($entry['evening']['text'] ?? '')) ?>
            <?php if (!empty($entry['evening']['photos'])): ?>
            <div class="photos">
                <?php foreach ($entry['evening']['photos'] as $photo): ?>
                <img src="<?= $photo ?>" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</body>
</html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esporta Diario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        h1 { color: #8B4513; }
        .export-btn {
            display: inline-block;
            margin: 20px 10px;
            padding: 15px 30px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 18px;
        }
        .export-btn:hover {
            background: #A0522D;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #8B4513;
        }
    </style>
</head>
<body>
    <h1>Esporta il tuo Diario di Viaggio</h1>
    <p>Scegli il formato per esportare il tuo diario:</p>
    
    <a href="?format=json" class="export-btn">📄 Esporta JSON</a>
    <a href="?format=html" class="export-btn">📖 Esporta HTML</a>
    
    <br>
    <a href="leggi.php" class="back-link">← Torna al diario</a>
</body>
</html>