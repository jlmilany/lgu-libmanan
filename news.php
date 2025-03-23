<?php
require_once 'config.php'; // Database connection

$admin_mode = (isset($_GET['admin']) && $_GET['admin'] === '1');

// Fetch news from the database
$news_list = [];
try {
    $stmt = $conn->prepare("SELECT id, title, created_at, content, video_url, image_path, video_file_path 
                            FROM news_content 
                            ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $news_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    die("Error fetching news: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>News Section</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    /* Ensure modal and backdrop are above other elements (if needed) */
    .modal-backdrop {
      z-index: 1090 !important;
    }
    /* Ensure the modal is always centered */
    .modal-dialog.custom-modal {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      max-width: 60%;
      margin: auto; /* Ensure centering */
    }
    .modal {
      z-index: 1100 !important;
    }
    /* Fullscreen overlay (for enlarged image) */
    .fullscreen-image {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1500;
      cursor: pointer;
    }
    .fullscreen-image img {
      max-width: 90%;
      max-height: 90%;
    }
    /* Close (X) button styling for fullscreen viewer */
    .close-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      font-size: 2rem;
      color: #fff;
      background: rgba(0,0,0,0.5);
      border: none;
      border-radius: 50%;
      padding: 0.2rem 0.5rem;
      z-index: 1600;
      cursor: pointer;
    }
    .close-btn:hover {
      color: red;
    }
    /* Existing styling for news cards */
    .news-container {
      display: flex;
      overflow-x: auto;
      gap: 15px;
      padding-bottom: 10px;
      scroll-snap-type: x mandatory;
      white-space: nowrap;
    }
    .news-item {
      flex: 0 0 auto;
      width: 320px;
      cursor: pointer;
      scroll-snap-align: start;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .news-item:hover {
      transform: scale(1.03);
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    }
    #modalImage {
      width: 100%;
      height: auto;
      cursor: pointer;
    }
    .news-date {
      font-size: 0.9rem;
      color: gray;
    }
    .custom-modal {
    max-width: 800px; /* Set a fixed width */
    width: 90%; /* Responsive width */
    }
    .modal-dialog {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }
    .modal-body {
      overflow-y: auto;
      max-height: 80vh;
      word-wrap: break-word;
      white-space: normal;
    }
    .news-item .card-text {
      white-space: normal;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      max-height: 4.5em;
    }
    .news-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 5px;
    }
    .news-card {
      width: 280px;
      height: 350px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
      border-radius: 6px;
      box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.1);
    }
    .news-content {
      padding: 6px;
      text-align: center;
      flex-grow: 1;
    }
    .news-title {
      font-size: 14px;
      font-weight: bold;
      white-space: normal;
      overflow: hidden;
      text-overflow: ellipsis;
      max-height: 40px;
    }
    .news-description {
      font-size: 12px;
      max-height: 60px;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .news-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 6px;
      border-top-right-radius: 6px;
    }
    .card-img-top {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    @media (max-width: 768px) {
      .news-card {
        width: 200px;
        height: 320px;
        margin-left: 100px;
      }
      .news-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
      }
      #newsModal {
        max-width: 50vh;
      }
      .modal-content {
        margin-left: 22px;
      }
    }
    
    /* Custom modal width for desktop */
    @media (min-width: 992px) {
      .modal-dialog.custom-modal {
        max-width: 60%;
      }
    }
  </style>
</head>
<body>

  <div class="container my-4">
    <h2 class="text-primary">Latest News</h2>
    <div class="news-container">
      <?php foreach ($news_list as $news): ?>
        <div class="news-item card"
             data-title="<?php echo htmlspecialchars($news['title']); ?>"
             data-date="<?php echo date('F d, Y', strtotime($news['created_at'])); ?>"
             data-content="<?php echo nl2br(htmlspecialchars($news['content'])); ?>"
             data-image="<?php echo htmlspecialchars($news['image_path']); ?>">
          <?php if (!empty($news['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($news['image_path']); ?>" class="card-img-top" alt="News Image">
          <?php endif; ?>
          <div class="card-body">
            <h5 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h5>
            <p class="news-date"><?php echo date('F d, Y', strtotime($news['created_at'])); ?></p>
            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($news['content'], 0, 100))) . '...'; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- News Modal -->
  <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered custom-modal">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="newsModalLabel"></h5>
            <p class="news-date" id="newsModalDate"></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <img id="modalImage" src="" class="img-fluid mb-3" alt="News Image" onclick="showFullscreenImage(this)">
          <p id="newsContent"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Fullscreen Image Viewer -->
  <div id="fullscreenViewer" class="fullscreen-image" onclick="hideFullscreenImage()">
    <button class="close-btn" onclick="hideFullscreenImage(); event.stopPropagation();">✖</button>
    <img id="fullscreenImg" src="" alt="Fullscreen View">
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Create the modal instance in the global scope
    let newsModal;
    document.addEventListener("DOMContentLoaded", function () {
      const newsModalEl = document.getElementById('newsModal');
      newsModal = new bootstrap.Modal(newsModalEl);

      // Attach click event on each news card to open the modal
      document.querySelectorAll('.news-item').forEach(function(card) {
        card.addEventListener('click', function() {
          const title   = card.dataset.title;
          const date    = card.dataset.date;
          const content = card.dataset.content;
          const image   = card.dataset.image;
          
          // Populate modal content
          document.getElementById('newsModalLabel').textContent = title;
          document.getElementById('newsModalDate').textContent  = date;
          document.getElementById('newsContent').innerHTML      = content;
          
          const modalImage = document.getElementById('modalImage');
          if (image && image.trim() !== "") {
            modalImage.src = image;
            modalImage.style.display = 'block';
          } else {
            modalImage.style.display = 'none';
            modalImage.src = '';
          }
          
          // Show the modal
          newsModal.show();
        });
      });

      // Explicit close button event (optional, as Bootstrap handles data-bs-dismiss)
      const modalCloseBtn = document.querySelector('#newsModal .btn-close');
      modalCloseBtn.addEventListener('click', function() {
        newsModal.hide();
      });
    });

    // Show fullscreen view of the image when clicked
    function showFullscreenImage(img) {
      const fullscreenViewer = document.getElementById("fullscreenViewer");
      const fullscreenImg = document.getElementById("fullscreenImg");
      fullscreenImg.src = img.src;
      fullscreenViewer.style.display = "flex";
    }

    // Hide fullscreen view
    function hideFullscreenImage() {
      document.getElementById("fullscreenViewer").style.display = "none";
    }
  </script>
  
</body>
</html>
