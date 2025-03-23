<?php
include('config.php');

$office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

$whereClauses = [];
if ($office_id) $whereClauses[] = "office_id = $office_id";
if ($section_id) $whereClauses[] = "section_id = $section_id";

$query = "SELECT * FROM announcements_content";
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}
$query .= " ORDER BY id DESC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcements</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color:rgb(255, 255, 255); /* Ensure this is the desired background color */
      margin: 0;
      padding: 20px;
      color: #333;
    }
    h1.page-title {
      text-align: center;
      margin-bottom: 30px;
      font-size: 45px;
      font-weight: bold;
      padding-top: 50px;
    }
    .announcements-scroll-container {
      display: flex;
      gap: 20px;
      overflow-x: auto;
      padding: 10px;
      scroll-behavior: smooth;
    }
    .announcement-card {
      min-width: 280px;
      max-width: 300px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      flex-shrink: 0;
    }
    .announcement-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .card-image {
      width: 100%;
      max-height: 180px;
      object-fit: contain;
      background: #eee;
    }
    .card-content {
      padding: 15px;
    }
    .card-title {
      font-size: 20px;
      margin: 0 0 5px;
      font-weight: bold;
    }
    .card-caption {
      font-size: 16px;
      color: black;
      margin-top: 5px; /* Add some space between title and caption */
    }
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      padding: 50px;
      box-sizing: border-box;
    }
    .modal.active {
      display: flex;
    }
    .modal-content {
      background: #fff;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .modal-title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .modal-image {
      width: 100%;
      height: auto;
      object-fit: contain;
      display: block;
      margin-bottom: 15px;
      cursor: pointer;
    }
    /* Full-screen image modal */
    .full-image-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.8);
      justify-content: center;
      align-items: center;
      z-index: 1100; /* Higher than the modal */
      padding: 50px;
      box-sizing: border-box;
    }
    .full-image-modal.active {
      display: flex;
    }
    .full-image-content {
      position: relative;
      width: 90%;
      max-width: 800px;
    }
    .full-image {
      width: 100%;
      height: auto;
      display: block;
      border-radius: 5px;
    }
    /* Close buttons */
    .close-button {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 30px;
      color: white;
      cursor: pointer;
      background: rgba(0,0,0,0.6);
      padding: 5px 10px;
      border-radius: 5px;
      z-index: 1010;
    }
    .modal .close-button {
      color: black;
      background: rgba(255, 255, 255, 0.8);
    }
  </style>
</head>
<body>
  <h1 class="page-title">ANNOUNCEMENTS</h1>
  <div class="announcements-scroll-container">
    <?php while ($announcement = $result->fetch_assoc()): ?>
      <?php
        // Fetch the background color from the database
        $backgroundColor = !empty($announcement['background_color'])
            ? htmlspecialchars($announcement['background_color'])
            : '#fff'; // Default to white if no color is set
      ?>
      <div class="announcement-card" style="background-color: <?php echo $backgroundColor; ?>;" 
           data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
           data-caption="<?php echo htmlspecialchars($announcement['caption']); ?>"
           data-image="<?php echo htmlspecialchars($announcement['image_path']); ?>">
        <?php if (!empty($announcement['image_path'])): ?>
          <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" 
               alt="<?php echo htmlspecialchars($announcement['title']); ?>" 
               class="card-image">
        <?php endif; ?>
        <div class="card-content">
          <h2 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h2>
          <p class="card-caption"><?php echo nl2br(htmlspecialchars($announcement['caption'])); ?></p> <!-- Display caption directly -->
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Main Modal -->
  <div class="modal" id="modal">
    <div class="modal-content">
      <span class="close-button" id="modalClose">&times;</span>
      <img src="" alt="" class="modal-image" id="modalImage">
      <h2 class="modal-title" id="modalTitle"></h2>
      <p class="modal-caption" id="modalCaption"></p>
    </div>
  </div>

  <!-- Full-Size Image Modal -->
  <div class="full-image-modal" id="fullImageModal">
    <div class="full-image-content">
      <span class="close-button" id="closeFullImage">&times;</span>
      <img src="" alt="Full Image" class="full-image" id="fullSizeImage">
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('modal');
      const modalClose = document.getElementById('modalClose');
      const modalImage = document.getElementById('modalImage');
      const modalTitle = document.getElementById('modalTitle');
      const modalCaption = document.getElementById('modalCaption');

      const fullImageModal = document.getElementById('fullImageModal');
      const fullSizeImage = document.getElementById('fullSizeImage');
      const closeFullImage = document.getElementById('closeFullImage');

      const announcementCards = document.querySelectorAll('.announcement-card');

      // Function to open modal with card data
      function openModal(card) {
        modalTitle.textContent = card.dataset.title;
        modalCaption.innerHTML = card.dataset.caption.replace(/\n/g, '<br>');
        modalImage.src = card.dataset.image;
        modal.classList.add('active');
      }

      // Add click event to each card
      announcementCards.forEach(card => {
        card.addEventListener('click', function() {
          openModal(this);
        });
      });

      modalClose.addEventListener('click', () => modal.classList.remove('active'));

      modalImage.addEventListener('click', function() {
        fullSizeImage.src = this.src;
        fullImageModal.classList.add('active');
      });

      closeFullImage.addEventListener('click', () => fullImageModal.classList.remove('active'));
    });
  </script>
</body>
</html>