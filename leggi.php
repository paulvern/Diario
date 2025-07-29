<?php
// Configuration
$DIARY_FILE = 'diary-data.json';

// Load diary data
$diaryData = file_exists($DIARY_FILE) ? json_decode(file_get_contents($DIARY_FILE), true) : [];

// Sort entries by date (newest first)
krsort($diaryData);

// Filter by month if requested
$filterMonth = $_GET['month'] ?? 'all';
if ($filterMonth !== 'all') {
    $diaryData = array_filter($diaryData, function($key) use ($filterMonth) {
        return strpos($key, $filterMonth) === 0;
    }, ARRAY_FILTER_USE_KEY);
}

// Get available months
$allData = file_exists($DIARY_FILE) ? json_decode(file_get_contents($DIARY_FILE), true) : [];
$months = [];
foreach (array_keys($allData) as $date) {
    $month = substr($date, 0, 7);
    if (!in_array($month, $months)) {
        $months[] = $month;
    }
}
rsort($months);

// Calculate stats
$totalDays = count($allData);
$totalPhotos = 0;
$totalEntries = 0;
foreach ($allData as $entry) {
    foreach (['morning', 'afternoon', 'evening'] as $period) {
        if (!empty($entry[$period]['text'])) $totalEntries++;
        if (!empty($entry[$period]['photos'])) $totalPhotos += count($entry[$period]['photos']);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario di Viaggio - Lettura</title>
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
            margin-bottom: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            padding: 10px;
            background: #faf8f5;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #8B4513;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            min-width: max-content;
        }
        
        .filter-btn {
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            background: #8B4513;
            color: white;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #DEB887;
        }
        
        .day-entry {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            margin-left: 50px;
        }
        
        .day-entry::before {
            content: '📅';
            position: absolute;
            left: -50px;
            top: 20px;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .day-date {
            font-size: 20px;
            font-weight: 600;
            color: #8B4513;
            margin-bottom: 15px;
        }
        
        .time-period {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #faf8f5;
            border-radius: 10px;
        }
        
        .period-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .period-icon {
            font-size: 20px;
            margin-right: 8px;
        }
        
        .period-title {
            font-size: 16px;
            font-weight: 500;
            color: #666;
        }
        
        .period-text {
            margin-bottom: 10px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .photo-item {
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .photo-item:hover img {
            transform: scale(1.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
        
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            cursor: pointer;
        }
        
        .lightbox img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .lightbox.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📖 Diario di Viaggio</h1>
        <p>Modalità Lettura</p>
    </div>
    
    <div class="container">
        <?php if ($totalDays > 0): ?>
        <div class="stats-card">
            <h3>Il tuo viaggio in numeri</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalDays ?></div>
                    <div class="stat-label">Giorni</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $totalPhotos ?></div>
                    <div class="stat-label">Foto</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $totalEntries ?></div>
                    <div class="stat-label">Voci</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($months)): ?>
        <div class="filter-section">
            <div class="filter-buttons">
                <a href="?month=all" class="filter-btn <?= $filterMonth === 'all' ? 'active' : '' ?>">Tutti</a>
                <?php foreach ($months as $month): ?>
                    <?php 
                    $monthDate = DateTime::createFromFormat('Y-m', $month);
                    $monthName = $monthDate->format('F Y');
                    ?>
                    <a href="?month=<?= $month ?>" class="filter-btn <?= $filterMonth === $month ? 'active' : '' ?>">
                        <?= $monthName ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (empty($diaryData)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <p>Nessuna voce nel diario</p>
            <p style="font-size: 14px; margin-top: 10px; color: #ccc;">Inizia a scrivere il tuo viaggio!</p>
        </div>
        <?php else: ?>
        <div class="timeline">
            <?php foreach ($diaryData as $date => $entry): ?>
                <?php
                $hasContent = false;
                foreach (['morning', 'afternoon', 'evening'] as $period) {
                    if (!empty($entry[$period]['text']) || !empty($entry[$period]['photos'])) {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent) continue;
                
                $dateObj = DateTime::createFromFormat('Y-m-d', $date);
                ?>
                <div class="day-entry">
                    <div class="day-date">
                        <?= $dateObj->format('l, d F Y') ?>
                    </div>
                    
                    <?php
                    $periods = [
                        'morning' => ['icon' => '🌅', 'title' => 'Mattina'],
                        'afternoon' => ['icon' => '☀️', 'title' => 'Pomeriggio'],
                        'evening' => ['icon' => '🌙', 'title' => 'Sera']
                    ];
                    
                    foreach ($periods as $periodKey => $periodInfo):
                        if (empty($entry[$periodKey]['text']) && empty($entry[$periodKey]['photos'])) continue;
                    ?>
                    <div class="time-period">
                        <div class="period-header">
                            <span class="period-icon"><?= $periodInfo['icon'] ?></span>
                            <h3 class="period-title"><?= $periodInfo['title'] ?></h3>
                        </div>
                        
                        <?php if (!empty($entry[$periodKey]['text'])): ?>
                        <div class="period-text"><?= htmlspecialchars($entry[$periodKey]['text']) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($entry[$periodKey]['photos'])): ?>
                        <div class="photo-grid">
                            <?php foreach ($entry[$periodKey]['photos'] as $photo): ?>
                            <div class="photo-item" onclick="openLightbox('<?= $photo ?>')">
                                <img src="<?= $photo ?>" alt="">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <nav class="bottom-nav">
        <a href="diario.php" class="nav-item">
            <span style="display: block; font-size: 24px;">✏️</span>
            <span style="font-size: 12px;">Scrivi</span>
        </a>
        <a href="leggi.php" class="nav-item active">
            <span style="display: block; font-size: 24px;">📖</span>
            <span style="font-size: 12px;">Leggi</span>
        </a>
    </nav>
    
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <img id="lightboxImg" src="" alt="">
    </div>
    
    <script>
    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightbox').classList.add('active');
    }
    
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
    }
    
    // Chiudi lightbox con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
    </script>
</body>
</html>