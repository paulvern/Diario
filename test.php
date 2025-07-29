<?php
session_start();

// Configuration
$DIARY_FILE = 'diary-data.json';
$UPLOAD_DIR = 'uploads/';

// Create directories if needed
if (!file_exists($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0777, true);
}

// Initialize diary file
if (!file_exists($DIARY_FILE)) {
    file_put_contents($DIARY_FILE, '{}');
}

// Load diary data
$diaryData = json_decode(file_get_contents($DIARY_FILE), true) ?: [];

// Get current date or selected date
$currentDate = $_GET['date'] ?? date('Y-m-d');
$dateObj = new DateTime($currentDate);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $date = $_POST['date'];
        
        // Save text entries
        $diaryData[$date] = [
            'date' => $date,
            'morning' => [
                'text' => $_POST['morning_text'] ?? '',
                'photos' => json_decode($_POST['morning_photos'] ?? '[]', true)
            ],
            'afternoon' => [
                'text' => $_POST['afternoon_text'] ?? '',
                'photos' => json_decode($_POST['afternoon_photos'] ?? '[]', true)
            ],
            'evening' => [
                'text' => $_POST['evening_text'] ?? '',
                'photos' => json_decode($_POST['evening_photos'] ?? '[]', true)
            ]
        ];
        
        // Handle photo uploads
        foreach (['morning', 'afternoon', 'evening'] as $period) {
            if (isset($_FILES[$period . '_photo']) && $_FILES[$period . '_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$period . '_photo'];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . $extension;
                $filepath = $UPLOAD_DIR . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $diaryData[$date][$period]['photos'][] = $filepath;
                }
            }
        }
        
        // Remove empty entries
        if (empty($diaryData[$date]['morning']['text']) && empty($diaryData[$date]['morning']['photos']) &&
            empty($diaryData[$date]['afternoon']['text']) && empty($diaryData[$date]['afternoon']['photos']) &&
            empty($diaryData[$date]['evening']['text']) && empty($diaryData[$date]['evening']['photos'])) {
            unset($diaryData[$date]);
        }
        
        // Save to file
        file_put_contents($DIARY_FILE, json_encode($diaryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Redirect to avoid resubmission
        header("Location: diario.php?date=$date&saved=1");
        exit;
    }
    
    if ($action === 'delete_photo') {
        $date = $_POST['date'];
        $period = $_POST['period'];
        $photoIndex = $_POST['photo_index'];
        
        if (isset($diaryData[$date][$period]['photos'][$photoIndex])) {
            // Delete file
            $photoPath = $diaryData[$date][$period]['photos'][$photoIndex];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
            
            // Remove from array
            array_splice($diaryData[$date][$period]['photos'], $photoIndex, 1);
            
            // Save
            file_put_contents($DIARY_FILE, json_encode($diaryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        header("Location: diario.php?date=$date");
        exit;
    }
}

// Get current entry
$currentEntry = $diaryData[$currentDate] ?? [
    'morning' => ['text' => '', 'photos' => []],
    'afternoon' => ['text' => '', 'photos' => []],
    'evening' => ['text' => '', 'photos' => []]
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario di Viaggio - Scrivi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: #faf8f5;
            color: #333;
            padding-bottom: 80px;
        }
        
        .header {
            background: linear-gradient(135deg, #8B4513, #DEB887);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .date-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .date-nav a {
            text-decoration: none;
            background: #8B4513;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .period-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .period-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 20px;
            color: #666;
        }
        
        .period-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
        }
        
        .photo-upload {
            margin-top: 15px;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .photo-item {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .delete-photo {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
        }
        
        .save-button {
            background: #8B4513;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            display: block;
            margin: 30px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .save-button:hover {
            background: #A0522D;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-item {
            flex: 1;
            text-align: center;
            text-decoration: none;
            color: #666;
            padding: 10px;
        }
        
        .nav-item.active {
            color: #8B4513;
            font-weight: bold;
        }
        
        .file-input-label {
            display: inline-block;
            background: #f0f0f0;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .file-input-label:hover {
            background: #e0e0e0;
        }
        
        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✏️ Diario di Viaggio</h1>
        <p>Modalità Scrittura</p>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['saved'])): ?>
        <div class="success-message">
            ✓ Salvato con successo!
        </div>
        <?php endif; ?>
        
        <div class="date-nav">
            <a href="?date=<?= $dateObj->modify('-1 day')->format('Y-m-d') ?>">‹</a>
            <h2><?= (new DateTime($currentDate))->format('d F Y') ?></h2>
            <a href="?date=<?= $dateObj->modify('+2 day')->format('Y-m-d') ?>">›</a>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="date" value="<?= $currentDate ?>">
            
            <!-- Mattina -->
            <div class="period-section">
                <div class="period-header">
                    <span class="period-icon">🌅</span>
                    <h3>Mattina</h3>
                </div>
                <textarea name="morning_text" placeholder="Cosa hai fatto stamattina?"><?= htmlspecialchars($currentEntry['morning']['text']) ?></textarea>
                
                <div class="photo-upload">
                    <label class="file-input-label">
                        📷 Aggiungi foto
                        <input type="file" name="morning_photo" accept="image/*">
                    </label>
                </div>
                
                <?php if (!empty($currentEntry['morning']['photos'])): ?>
                <div class="photo-grid">
                    <?php foreach ($currentEntry['morning']['photos'] as $index => $photo): ?>
                    <div class="photo-item">
                        <img src="<?= $photo ?>" alt="">
                        <button type="submit" name="action" value="delete_photo" class="delete-photo" 
                                onclick="this.form.period.value='morning'; this.form.photo_index.value=<?= $index ?>">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <input type="hidden" name="morning_photos" value='<?= json_encode($currentEntry['morning']['photos']) ?>'>
            </div>
            
            <!-- Pomeriggio -->
            <div class="period-section">
                <div class="period-header">
                    <span class="period-icon">☀️</span>
                    <h3>Pomeriggio</h3>
                </div>
                <textarea name="afternoon_text" placeholder="Com'è andato il pomeriggio?"><?= htmlspecialchars($currentEntry['afternoon']['text']) ?></textarea>
                
                <div class="photo-upload">
                    <label class="file-input-label">
                        📷 Aggiungi foto
                        <input type="file" name="afternoon_photo" accept="image/*">
                    </label>
                </div>
                
                <?php if (!empty($currentEntry['afternoon']['photos'])): ?>
                <div class="photo-grid">
                    <?php foreach ($currentEntry['afternoon']['photos'] as $index => $photo): ?>
                    <div class="photo-item">
                        <img src="<?= $photo ?>" alt="">
                        <button type="submit" name="action" value="delete_photo" class="delete-photo" 
                                onclick="this.form.period.value='afternoon'; this.form.photo_index.value=<?= $index ?>">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <input type="hidden" name="afternoon_photos" value='<?= json_encode($currentEntry['afternoon']['photos']) ?>'>
            </div>
            
            <!-- Sera -->
            <div class="period-section">
                <div class="period-header">
                    <span class="period-icon">🌙</span>
                    <h3>Sera</h3>
                </div>
                <textarea name="evening_text" placeholder="Come si è conclusa la giornata?"><?= htmlspecialchars($currentEntry['evening']['text']) ?></textarea>
                
                <div class="photo-upload">
                    <label class="file-input-label">
                        📷 Aggiungi foto
                        <input type="file" name="evening_photo" accept="image/*">
                    </label>
                </div>
                
                <?php if (!empty($currentEntry['evening']['photos'])): ?>
                <div class="photo-grid">
                    <?php foreach ($currentEntry['evening']['photos'] as $index => $photo): ?>
                    <div class="photo-item">
                        <img src="<?= $photo ?>" alt="">
                        <button type="submit" name="action" value="delete_photo" class="delete-photo" 
                                onclick="this.form.period.value='evening'; this.form.photo_index.value=<?= $index ?>">×</button>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <input type="hidden" name="evening_photos" value='<?= json_encode($currentEntry['evening']['photos']) ?>'>
            </div>
            
            <input type="hidden" name="period" value="">
            <input type="hidden" name="photo_index" value="">
            
            <button type="submit" class="save-button">💾 Salva Giornata</button>
        </form>
    </div>
    
    <nav class="bottom-nav">
        <a href="diario.php" class="nav-item active">
            <span style="display: block; font-size: 24px;">✏️</span>
            <span style="font-size: 12px;">Scrivi</span>
        </a>
        <a href="leggi.php" class="nav-item">
            <span style="display: block; font-size: 24px;">📖</span>
            <span style="font-size: 12px;">Leggi</span>
        </a>
    </nav>
</body>
</html>