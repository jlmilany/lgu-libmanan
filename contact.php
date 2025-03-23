<?php
// templates/contact.php
// Expected variables:
//   $section: the current section record (with optional icon_path/background_path)
//   $admin_mode: boolean flag.
//   $office: the current office record.

// Decode the JSON content safely
$content = !empty($section['content']) ? json_decode($section['content'], true) : [];
$contact_email = $content['email'] ?? 'Not provided'; // Use null coalescing operator for safer access
$contact_phone = $content['phone'] ?? 'Not provided';

// Fetch the office address from the $office record
$office_location = $office['office_address'] ?? 'Not provided';

$admin_mode = (isset($_GET['admin']) && $_GET['admin'] == 1);
?>
<div id="section-<?php echo $section['id']; ?>" class="section contact-footer py-5" style="<?php 
  // Set background color if provided
  $background_color = !empty($section['background_color']) ? htmlspecialchars($section['background_color']) : '#f8f9fa';
  echo "background-color: $background_color;"; 
  // Set background image if provided
  if (!empty($section['background_path'])) {
    echo "background-image: url('" . htmlspecialchars($section['background_path']) . "'); background-size: cover; background-position: center;";
  }
?>">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="text-primary">Contact Us</h2>
      <p class="lead text-muted">Get in touch with us for any inquiries or support.</p>
    </div>

    <div class="row">
      <!-- Contact Form Column -->
      <div class="col-md-6 mb-4">
        <div class="contact-card-footer p-4 rounded" style="background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
          <h3 class="text-primary mb-4">Send Us a Message</h3>
          <form id="contactForm" method="POST" action="your_script.php"> <!-- Replace 'your_script.php' with your actual PHP script -->
            <div class="form-group">
              <label for="email" class="text-muted">Enter a valid email address</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
              <label for="message" class="text-muted">Your Message</label>
              <textarea id="message" name="message" class="form-control" rows="4" placeholder="Type your message here..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">SUBMIT</button>
          </form>
        </div>
      </div>

      <!-- Contact Info Column -->
      <div class="col-md-6 mb-4">
        <div class="contact-card-footer p-4 rounded" style="background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
          <h3 class="text-primary mb-4">Contact Information</h3>
          <div class="contact-info">
            <div class="d-flex align-items-center mb-3">
              <i class="fas fa-envelope fa-2x text-primary mr-3"></i>
              <div>
                <h5 class="mb-0">Email</h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_email); ?></p>
              </div>
            </div>
            <div class="d-flex align-items-center mb-3">
              <i class="fas fa-phone fa-2x text-primary mr-3"></i>
              <div>
                <h5 class="mb-0">Phone</h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_phone); ?></p>
              </div>
            </div>
            <div class="d-flex align-items-center mb-3">
              <i class="fas fa-map-marker-alt fa-2x text-primary mr-3"></i>
              <div>
                <h5 class="mb-0">Address</h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($office_location); ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .contact-footer {
    padding: 60px 0;
  }

  .contact-card-footer {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
  }

  .contact-card-footer:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  }

  .contact-card-footer h2, .contact-card-footer h3, .contact-card-footer h4 {
    color: #007bff; /* Primary color for headings */
  }

  .contact-card-footer .form-group label {
    color: #6c757d; /* Muted text color */
    font-size: 0.9em;
  }

  .contact-card-footer .form-control {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 10px;
    font-size: 1em;
  }

  .contact-card-footer .btn-primary {
    background-color: #007bff;
    border: none;
    padding: 10px 15px;
    font-size: 1em;
    transition: background-color 0.3s;
  }

  .contact-card-footer .btn-primary:hover {
    background-color: #0056b3;
  }

  .contact-info p {
    font-size: 1em;
    margin: 10px 0;
  }

  .contact-info i {
    margin-right: 10px;
    color: #007bff;
  }

  .lead {
    font-size: 1.25em;
    font-weight: 300;
  }
</style>