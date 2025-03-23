<?php
require_once 'config.php'; // Include database configuration

// Fetch the office ID from the URL
$officeId = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;

// Fetch gallery images and office details for the specified office
$galleryImages = [];
$officeName = '';
if ($officeId) {
    $resOffice = $conn->query("SELECT office_name FROM offices WHERE id = $officeId");
    if ($resOffice && $resOffice->num_rows > 0) {
        $officeData = $resOffice->fetch_assoc();
        $officeName = htmlspecialchars($officeData['office_name']);
    }

    $resGallery = $conn->query("SELECT gc.file_path, gc.caption, gc.title FROM gallery_content gc 
                                 JOIN sections s ON gc.section_id = s.id 
                                 WHERE s.office_id = $officeId 
                                 ORDER BY gc.created_at DESC");
    if ($resGallery && $resGallery->num_rows > 0) {
        while ($row = $resGallery->fetch_assoc()) {
            $galleryImages[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link rel="stylesheet" href="styles.css"> <!-- Ensure styles are linked -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .text-center{
            padding-top: 10px;
        }
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            justify-content: center;
            max-width: 1200px;
            margin: auto;
        }
        .gallery-item {
            position: relative;
            width: 100%;
            height: 250px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
            cursor: pointer;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        .image-title {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(90, 62, 62, 0.75);
            color: white;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gallery-item:hover .image-title {
            opacity: 1;
            padding: 10px;
        }
        .fullscreen-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
.no-scroll {
    overflow: hidden; /* Prevents scrolling */
}

        .fullscreen-modal img {
            max-width: 90%;
            max-height: 90%;
        }
        .fullscreen-modal.active {
            display: flex;
        }
        @media (max-width: 1024px) {
            .gallery-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .gallery-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .gallery-container {
                grid-template-columns: repeat(1, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="text-center">
    <h2><?php echo $officeName; ?> PROGRAMS<hr></h2>
    <p>Explore the various programs and initiatives under <?php echo $officeName; ?>. Click on an image to learn more.</p>
</div>


    <div class="gallery-container">
        <?php foreach ($galleryImages as $image): ?>
            <div class="gallery-item" onclick="openFullscreen('<?php echo htmlspecialchars($image['file_path']); ?>')">
                <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="<?php echo htmlspecialchars($image['caption']); ?>">
                <div class="image-title"> <?php echo htmlspecialchars($image['title']); ?> </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="fullscreen-modal" id="fullscreenModal" onclick="closeFullscreen()">
        <img id="fullscreenImage" src="" alt="Fullscreen Image">
    </div>
    <script>
        function openFullscreen(imageSrc) {
            document.getElementById('fullscreenImage').src = imageSrc;
            document.getElementById('fullscreenModal').classList.add('active');
        }
        function closeFullscreen() {
            document.getElementById('fullscreenModal').classList.remove('active');
        }

        function openFullscreen(imageSrc) {
    document.getElementById('fullscreenImage').src = imageSrc;
    document.getElementById('fullscreenModal').classList.add('active');
    document.body.classList.add('no-scroll'); // Disable scrolling on the background
}

function closeFullscreen() {
    document.getElementById('fullscreenModal').classList.remove('active');
    document.body.classList.remove('no-scroll'); // Re-enable scrolling
}

    </script>
</body>
</html>
