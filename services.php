<?php
// templates/services.php
// Expected variables:
//   $section: the current section record (with optional icon_path/background_path)
//   $admin_mode: boolean flag.
//   $office: the current office record.

// Database connection (assuming $conn is your database connection)
require_once 'config.php'; // Ensure you have your database connection set up

// Fetch services from the database
$services = [];
if (isset($section['id'])) {
    $sectionId = intval($section['id']);
    // Updated query to include the caption column
    $result = $conn->query("SELECT id, title, caption FROM services_content WHERE section_id = $sectionId");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Split the caption into title and description
            $captionParts = explode('||', $row['caption']);
            $description = isset($captionParts[1]) ? $captionParts[1] : ''; // Get the description part
            $row['description'] = $description; // Add description to the row
            $services[] = $row; // Add each service to the services array
        }
    }
}

$admin_mode = (isset($_GET['admin']) && $_GET['admin'] == 1);
?>
<div id="section-<?php echo htmlspecialchars($section['id']); ?>" class="section services my-4" style="<?php if (!empty($section['background_path'])) echo "background-image: url('" . htmlspecialchars($section['background_path']) . "');"; ?>">
  <div class="container text-center">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <?php if (!empty($section['icon_path'])) { ?>
         <img src="<?php echo htmlspecialchars($section['icon_path']); ?>" alt="Icon" style="max-width:50px;">
      <?php } ?>
      <?php if ($admin_mode) { ?>
         <a href="manage_offices.php?office_id=<?php echo htmlspecialchars($office['id']); ?>&section_id=<?php echo htmlspecialchars($section['id']); ?>" class="btn btn-sm btn-secondary">Manage Services</a>
      <?php } ?>
    </div>
    <!-- Center-aligned description with office name -->
    <h2 class="section-title align-items-center">Our Services</h2>
    <hr>
    <p class="office-description">
      At <?php echo htmlspecialchars($office['office_name']); ?>, we are committed to providing high-quality services to meet the needs of our community.
      Our team ensures efficient and reliable assistance in various areas to serve the public with professionalism and excellence.
    </p>
    <!-- Horizontal Scrolling Container -->
    <div class="services-scroll-container">
      <div class="services-scroll">
        <?php if (isset($services) && count($services) > 0): ?>
          <?php foreach ($services as $service): ?>
            <div class="service-item">
              <div class="service-content">
                <strong class="service-title"><?php echo htmlspecialchars($service['title']); ?></strong>
                <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
              </div>
              <?php if ($admin_mode) { ?>
                <a href="manage_offices.php?office_id=<?php echo htmlspecialchars($office['id']); ?>&section_id=<?php echo htmlspecialchars($section['id']); ?>&delete_service=<?php echo htmlspecialchars($service['id']); ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
              <?php } ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No services available.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- CSS for Horizontal Scrolling and Responsive Horizontal Cards -->
<style>
  /* Main container to hide overflow */
  .services-scroll-container {
    overflow: hidden;
    padding: 50px 0;
    margin: 0 auto;
    position: relative;
    width: 100%;
  }

  /* Scrolling container */
  .services-scroll {
    display: flex;
    gap: 25px;
    animation: scroll 50s linear infinite;
  }

  /* Default style for horizontal rectangular service card (for smaller devices) */
  .service-item {
    flex: 0 0 auto;
    width: 300px;      /* Default width on mobile/tablet */
    height: 150px;     /* Fixed height */
    padding: 15px 20px;
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  /* Text content container (left side) */
  .service-content {
    flex: 1;
    text-align: left;
  }

  /* Service title styling */
  .service-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 4px;
    display: block;
  }

  /* Service description styling */
  .service-description {
    font-size: 0.9rem;
    line-height: 1.3;
    margin: 0;
  }

  /* Delete Button (Admin Mode) */
  .btn-delete {
    margin-left: 20px;
    align-self: flex-start;
  }

  /* Hover effect for service cards */
  .service-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  }

  /* Responsive adjustments: On desktop, display 3 cards per view */
  @media (min-width: 992px) {
    .service-item {
      /* Calculate three cards per row:
         - If there are two gaps of 25px between three cards, subtract 50px from total width.
         - Each card gets an equal share of the container width. */
      flex: 0 0 calc((100% - 50px) / 3);
      width: auto;
    }
  }

  /* Scroll animation */
  @keyframes scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-150%); }
  }

  /* Adjustments for smaller screens */
  @media (max-width: 768px) {
    .service-item {
      width: 300px;
      height: 150px;
    }
    .services-scroll {
      gap: 20px;
    }
  }
</style>

<script>
  const scrollContainer = document.querySelector('.services-scroll-container');
  const scrollContent = document.querySelector('.services-scroll');

  // Duplicate scrolling content for an infinite scroll effect
  scrollContent.innerHTML += scrollContent.innerHTML;

  // Slow, continuous horizontal scrolling
  const scrollSpeed = 0.05;
  let scrollWidth = scrollContent.scrollWidth / 2;

  function scroll() {
    scrollContainer.scrollLeft += scrollSpeed;
    if (scrollContainer.scrollLeft >= scrollWidth) {
      scrollContainer.scrollLeft = 0;
    }
    requestAnimationFrame(scroll);
  }

  requestAnimationFrame(scroll);
</script>