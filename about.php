<?php
//NOTE: DO NOT TOUCH!
// templates/about.php
// Expected variables:
//   $section: The current section record (with optional icon_path/background_path)
//   $admin_mode: Boolean flag indicating if admin mode is active
//   $office: The current office record

$admin_mode = isset($_GET['admin']) && $_GET['admin'] == 1;
$background_style = !empty($section['background_path'])
    ? "background-image: url('" . htmlspecialchars($section['background_path'], ENT_QUOTES, 'UTF-8') . "'); background-size: cover; background-position: center;"
    : "";

// Fetch about content from the database
$about_contents = [];
if (isset($section['id'])) { 
    $about_contents = $conn->query("SELECT * FROM about_content WHERE section_id = " . intval($section['id']));
}
?>

<div id="section-<?php echo htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8'); ?>" class="section about my-4 p-4 rounded shadow" style="<?php echo $background_style; ?>">
  <div class="container">
    <!-- Icon and Admin Button Row -->
    <div class="row justify-content-center align-items-center">
      <div class="col-12 col-md-3 mx-auto text-center">
        <?php if (!empty($section['icon_path'])): ?>
          <img src="<?php echo htmlspecialchars($section['icon_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Icon" class="img-fluid" style="max-width: 50px;">
        <?php endif; ?>
        <?php if ($admin_mode): ?>
          <a href="manage_offices.php?office_id=<?php echo htmlspecialchars($office['id'], ENT_QUOTES, 'UTF-8'); ?>&section_id=<?php echo htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary mt-3">
            Manage About
          </a>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- About Title and Description -->
    <div class="text-center mt-4">
      <h4>ABOUT <?php echo htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
      <hr>
      <p class="lead">
        Welcome to the <?php echo htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8'); ?>! Here you can learn more about our mission, vision, and the services we offer.
      </p>
    </div>
    
    <!-- About Content Cards -->
    <div class="row mt-4 justify-content-center text-center">
      <?php if ($about_contents && $about_contents->num_rows > 0): ?>
        <div class="col-md-12">
          <div class="row justify-content-center">
            <?php if ($about_contents->num_rows == 1): ?>
              <?php $about = $about_contents->fetch_assoc(); ?>
              <div class="col-12 mb-4 d-flex align-items-stretch">
                <div class="card w-100 shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($about['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                    <p class="card-text">
                      <?php echo nl2br(htmlspecialchars($about['content'], ENT_QUOTES, 'UTF-8')); ?>
                    </p>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <?php while ($about = $about_contents->fetch_assoc()): ?>
                <?php if ($about['content_type'] == 'text'): ?>
                  <div class="col-12 col-md-4 mb-4 d-flex align-items-stretch">
                    <div class="card w-100 shadow-sm">
                      <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($about['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                        <p class="card-text">
                          <?php echo nl2br(htmlspecialchars($about['content'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </div>
    
        <!-- Image Modal Buttons -->
        <div class="col-md-12 text-center mt-3">
          <h5>Know More</h5>
          <div class="d-flex justify-content-center align-items-center flex-wrap">
            <?php
            // Reset the pointer for image buttons
            $about_contents->data_seek(0);
            while ($about = $about_contents->fetch_assoc()):
              if ($about['content_type'] == 'image' && !empty($about['image_path'])): ?>
                <div class="m-2">
                  <button class="btn btn-primary" data-toggle="modal" data-target="#imageModal-<?php echo htmlspecialchars($about['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    View <?php echo htmlspecialchars($about['title'], ENT_QUOTES, 'UTF-8'); ?>
                  </button>
                </div>
    
                <!-- Modal -->
                <div class="modal fade" id="imageModal-<?php echo htmlspecialchars($about['id'], ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel-<?php echo htmlspecialchars($about['id'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel-<?php echo htmlspecialchars($about['id'], ENT_QUOTES, 'UTF-8'); ?>">
                          <?php echo htmlspecialchars($about['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body text-center">
                        <img src="<?php echo htmlspecialchars($about['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="About Image"
                             class="img-fluid"
                             id="modalImage-<?php echo htmlspecialchars($about['id'], ENT_QUOTES, 'UTF-8'); ?>"
                             onclick="openFullScreen(this)"
                             data-toggle="tooltip"
                             data-placement="top"
                             title="Click to view fullscreen">
                        <p class="mt-3">
                          <?php echo nl2br(htmlspecialchars($about['content'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif;
            endwhile; ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-center">No content available for this section.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Custom CSS for hover effects, modal sizing, and responsive image adjustments -->
<style>
  .card {
    transition: transform 0.2s;
  }
  .card:hover {
    transform: scale(1.05);
  }

  /* Modal z-index adjustments */
  .modal-backdrop {
    z-index: 1040 !important;
  }
  .modal {
    z-index: 1050 !important;
  }

  /* Default modal size for larger screens */
  .modal-dialog {
    max-width: 50%;
    margin: 30px auto;
    height: 90vh; /* Fixed height on larger screens */
    display: flex;
    align-items: center;
  }
  .modal-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  .modal-header,
  .modal-footer {
    flex: 0 0 auto;
  }
  /* Allow modal body to scroll when descriptions are long */
  .modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
  }
  /* Responsive image styling */
  .modal-body img {
    max-height: 100vh;
    max-width: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    margin: 0 auto;
    display: block;
    cursor: pointer;
  }

  /* Responsive modal adjustments for mobile devices */
  @media (max-width: 576px) {
    .modal-dialog {
      max-width: 90%;
      margin: 15px auto;
      /* Remove fixed height so it adjusts automatically on mobile */
      height: auto;
    }
    .modal-content {
      max-height: calc(100vh - 30px); /* Ensure modal does not exceed viewport height */
    }
  }
</style>

<!-- JavaScript for Enhanced Full-Screen Mode and Bootstrap Tooltip Initialization -->
<script>
function openFullScreen(img) {
  // Toggle fullscreen: request fullscreen if not already enabled; otherwise, exit fullscreen.
  if (!document.fullscreenElement &&
      !document.mozFullScreenElement &&
      !document.webkitFullscreenElement &&
      !document.msFullscreenElement) {
    if (img.requestFullscreen) {
      img.requestFullscreen();
    } else if (img.mozRequestFullScreen) { // Firefox
      img.mozRequestFullScreen();
    } else if (img.webkitRequestFullscreen) { // Chrome, Safari, Opera
      img.webkitRequestFullscreen();
    } else if (img.msRequestFullscreen) { // IE/Edge
      img.msRequestFullscreen();
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
  }
}

// Initialize Bootstrap tooltips once the DOM is ready.
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
</script>