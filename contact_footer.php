<?php
// templates/contact_footer.php
// Expected variables:
//   $footer_email: or simply use $section['content'] as the footer email.
//   $section: the current section record (with optional icon_path/background_path)
//   $admin_mode: boolean flag.
//   $office: the current office record.
$admin_mode = (isset($_GET['admin']) && $_GET['admin'] == 1);
$footer_email = (!empty($footer_email)) ? $footer_email : $section['content'];
?>
<div id="section-<?php echo $section['id']; ?>" class="section contact-footer my-4" style="<?php if(!empty($section['background_path'])) echo "background-image: url('" . htmlspecialchars($section['background_path']) . "');"; ?>">
  <div class="container text-center">
    <p>Contact us at <a href="mailto:<?php echo htmlspecialchars($footer_email); ?>"><?php echo htmlspecialchars($footer_email); ?></a></p>
    <p>&copy; <?php echo date("Y"); ?> Office Management System</p>
    <?php if($admin_mode){ ?>
      <a href="manage_offices.php?office_id=<?php echo $office['id']; ?>&section_id=<?php echo $section['id']; ?>" class="btn btn-sm btn-secondary">Manage Footer</a>
    <?php } ?>
  </div>
</div>
