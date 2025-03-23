<?php
// templates/hero.php
// Expected variables:
//   $section: its content (JSON) should include "headline" and "tagline"
//   $admin_mode: boolean flag to trigger admin-only controls.
//   $office: the current office record.
$admin_mode = (isset($_GET['admin']) && $_GET['admin'] == 1);
$heroData = json_decode($section['content'], true);
$headline = isset($heroData['headline']) ? $heroData['headline'] : '';
$tagline = isset($heroData['tagline']) ? $heroData['tagline'] : '';
?>
<div id="section-<?php echo $section['id']; ?>" class="section hero position-relative" style="<?php 
    if (!empty($section['background_path'])) {
      echo "background-image: url('" . htmlspecialchars($section['background_path']) . "');";
    }
  ?>">
  <div class="overlay"></div> <!-- Dark overlay for readability -->

  <!-- Full-width container by removing default side padding -->
  <div class="container-fluid h-100 position-relative">
    <div class="row h-100 align-items-center">
      <!-- Left Side: Text Content -->
      <div class="col-md-6 hero-content">
        <h1 class="hero-heading"><?php echo htmlspecialchars($headline); ?></h1>
        <p class="hero-tagline"><?php echo htmlspecialchars($tagline); ?></p>
        <div>
          <a href="#about" class="btn btn-primary mt-3 btn-learn-more" id="learnMoreBtn">Learn More</a>
        </div>
      </div>

      <!-- Right Side: Logo -->
      <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center">
        <a class="navbar-brand" href="#">
          <?php if (!empty($office['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars($office['logo_path']); ?>"
                 alt="<?php echo htmlspecialchars($office['office_name']); ?>"
                 class="office-logo">
          <?php else: ?>
            <span><?php echo htmlspecialchars($office['office_name']); ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</div>

<style>
  /* Base Hero Section Styles */
  .hero {
    position: relative;
    background-size: cover;
    background-position: center;
    color: white;
    min-height: 100vh; /* full viewport height */
    margin: 0;
    padding-top: 80px; /* ensure content sits below a fixed navbar */
    overflow: hidden;
    
  }

  /* Remove default horizontal padding/gutters */
  .hero .container-fluid,
  .hero .row {
    padding: 15px;
    margin: 0;
  }

  .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
  }

  /* Hero Content remains left aligned */
  .hero-content {
    text-align: left;
  }

  .hero-heading {
    font-size: 3rem;
    font-weight: bold;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 1s ease-in-out;
    margin-bottom: 20px;
  }

  .hero-tagline {
    font-size: 1.5rem;
    animation: fadeInUp 1.2s ease-in-out;
    margin-bottom: 20px;
  }

  .btn-learn-more {
    background:rgb(80, 153, 255);
    border: none;
    padding: 15px 30px;
    color: white;
    font-weight: bold;
    border-radius: 5px;
    transition: transform 0.3s, background 0.3s;
  }

  .btn-learn-more:hover {
    background:rgb(34, 152, 255);
    transform: scale(1.05);
    text-decoration: none;
  }

  /* Office Logo Styling */
  .office-logo {
    max-height: 550px;
    width: auto;
    animation: fadeInRight 1s ease-in-out;
    padding-top: 10%;

  }

  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeInRight {
    from {
      opacity: 0;
      transform: translateX(20px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .hero {
      padding-top: 100px; /* adjust top padding as needed */
      padding: -50px;
    }

    .hero-heading {
      font-size: 2.5rem;
      margin-top: 20vh;
    }

    .hero-tagline {
      font-size: 1.25rem;
    }

    .btn-learn-more {
      padding: 12px 40px;
      margin-top: 20px;
    }

    /* Stack columns vertically with left aligned text */
    .row {
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
    }
  }
</style>