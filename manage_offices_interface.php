<?php
session_start();
require_once 'config.php';
require_once 'manage_offices_backend.php'; // Include backend operations

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database to get their assigned office ID
$sql = "SELECT office_id FROM accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Access denied: User not found.");
}

$user = $result->fetch_assoc();
$user_office_id = $user['office_id'];
$stmt->close();

// Check if an office_id is provided in the URL
if (isset($_GET['office_id'])) {
    $requested_office_id = intval($_GET['office_id']);

    // Verify that the requested office matches the user's assigned office
    if ($requested_office_id !== $user_office_id) {
        die("Access denied: You do not have permission to access this office.");
    }
} else {
    // Redirect to the user's assigned office
    header("Location: manage_offices_interface.php?office_id=" . $user_office_id);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Manage Offices</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <style>
        .appearance-form {
            background: #f9f9f9;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #ddd;
        }

        .nav-public {
            margin-top: 20px;
            padding: 10px;
            background: #e9ecef;
        }

        .media-img {
            max-width: 100%;
            height: auto;
        }

        .modal-body img {
            max-width: 100%;
            height: auto;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Overall Side Navigation Styles */
        .sidenav {
            width: 260px;
            min-width: 250px;
            background-color: #318CE7;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
            transition: width 0.3s ease;
        }

        .sidenav h4 {
            text-align: center;
        }

        /* Responsive: Adjust sidenav on tablets & mobile */
        @media (max-width: 768px) {
            .sidenav {
                width: 100%;
                height: auto;
                position: relative;
            }
        }

        /* Logo Container Styling */
        .sidenav .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidenav .logo-container img.office-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidenav .logo-container .office-name {
            font-size: 1.5em;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .sidenav .logo-container p {
            font-size: 0.9em;
            margin-top: 5px;
        }

        /* User Info Styling */
        .sidenav .user-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidenav .user-info h5 {
            margin: 0;
        }

        /* Navigation Links Styling */
        .sidenav .nav-links h4 {
            border-bottom: 1px solid #495057;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .sidenav .nav-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidenav .nav-links ul li {
            margin-bottom: 10px;
        }

        .sidenav .nav-links ul li a {
            color: #fff;
            text-decoration: none;
            padding: 8px 10px;
            display: block;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .sidenav .nav-links ul li a:hover {
            background: #495057;
        }

        /* Extra adjustments for extra small screens */
        @media (max-width: 480px) {
            .sidenav {
                padding: 10px;
            }

            .sidenav .logo-container img.office-logo {
                width: 80px;
                height: 80px;
            }

            .sidenav .nav-links ul li a {
                padding: 6px 8px
            }
        }

        .main {
            margin-left: 270px;
        }
    </style>
</head>

<body>
    <div class="sidenav">
        <div class="logo-container">
            <?php if (!empty($selected_office['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($selected_office['logo_path']); ?>"
                    alt="<?php echo htmlspecialchars($selected_office['office_name']); ?>" class="office-logo">
            <?php endif; ?>
            <span class="office-name"><?php echo htmlspecialchars($selected_office['office_name']); ?></span>
            <p>Address: <?php echo htmlspecialchars($selected_office['office_address']); ?></p>
        </div>
        <div class="user-info">
            <h5>Welcome, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></h5>
        </div>
        <nav class="nav-links">
            <?php if ($selected_office) { ?>
                <br>
                <h4>Operations</h4>
                <ul>
                    <li>
                        <a href="#" data-toggle="modal" data-target="#uploadLogoModal">Upload Office Logo</a>
                    </li>
                    <li>
                        <a href="#" data-toggle="modal" data-target="#addSectionModal">Add Section</a>
                    </li>
                    <li>
                        <a href="#" data-toggle="modal" data-target="#updateAppearanceModal">Update Section Background</a>
                    </li>
                </ul>
            <?php } ?>
        </nav>
    </div>


    <div class="container mt-5 main">
        <div class="row">
            <!-- Sidebar: Offices and Sections List -->
            <div class="col-md-4">
                <?php if ($selected_office) { ?>
                    <h4>Office Section Panel
                        <hr>
                    </h4>



                    <?php
                    $secRes = $conn->query("SELECT * FROM sections WHERE office_id = {$selected_office['id']} ORDER BY created_at ASC");
                    if ($secRes && $secRes->num_rows > 0) {
                        echo "<ul class='list-group mb-4'>";
                        while ($sec = $secRes->fetch_assoc()) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                            echo ucfirst(str_replace('_', ' ', $sec['section_type']));
                            echo "<span>
                      <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$sec['id']}' class='btn btn-sm btn-secondary mr-2'>Edit</a>
                      <a href='manage_offices_interface.php?office_id={$selected_office['id']}&delete_section={$sec['id']}' class='btn btn-sm btn-danger'>Delete</a>
                    </span>";
                            echo "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No sections added yet.</p>";
                    }
                    ?>

                    <!-- Public Navigation (example) -->
                    <div class="nav-public card p-3">
                        <h5>Public Navigation</h5>
                        <ul class="list-group list-group-flush">
                            <?php
                            $secRes2 = $conn->query("SELECT * FROM sections WHERE office_id = {$selected_office['id']} ORDER BY created_at ASC");
                            if ($secRes2 && $secRes2->num_rows > 0) {
                                while ($sec2 = $secRes2->fetch_assoc()) {
                                    echo "<li class='list-group-item'>";
                                    echo "<a href='view_webpage.php?office_id={$selected_office['id']}#section-{$sec2['id']}'>";
                                    echo ucfirst(str_replace('_', ' ', $sec2['section_type']));
                                    echo "</a>";
                                    echo "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>


            <!-- Main Column: Office Info and Section Management -->
            <div class="col-md-8">
                <?php if ($selected_office) { ?>

                    <!-- Upload Office Logo Modal -->
                    <div class="modal fade" id="uploadLogoModal" tabindex="-1" role="dialog"
                        aria-labelledby="uploadLogoModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="uploadLogoModalLabel">Upload Office Logo</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form method="POST"
                                    action="manage_offices_interface.php?office_id=<?php echo $selected_office['id']; ?>"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="upload_logo">
                                    <input type="hidden" name="office_id" value="<?php echo $selected_office['id']; ?>">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="office_logo">Select Logo:</label>
                                            <input type="file" name="office_logo" id="office_logo" class="form-control"
                                                required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Upload Logo</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Add Section Modal -->
                    <div class="modal fade" id="addSectionModal" tabindex="-1" role="dialog"
                        aria-labelledby="addSectionModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form method="POST"
                                    action="manage_offices_interface.php?office_id=<?php echo $selected_office['id']; ?>">
                                    <input type="hidden" name="action" value="add_section">
                                    <input type="hidden" name="office_id" value="<?php echo $selected_office['id']; ?>">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="section_type">Section Type</label>
                                            <select name="section_type" id="section_type" class="form-control" required>
                                                <option value="">Select Template</option>
                                                <option value="hero">Hero</option>
                                                <option value="gallery">Gallery</option>
                                                <option value="about">About</option>
                                                <option value="services">Services</option>
                                                <option value="contact">Contact</option>
                                                <option value="news">News</option>
                                                <option value="announcements">Announcements</option>
                                                <option value="documents">Downloadable Documents</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Add Section</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Update Appearance Modal -->
                    <div class="modal fade" id="updateAppearanceModal" tabindex="-1" role="dialog"
                        aria-labelledby="updateAppearanceModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateAppearanceModalLabel">Update Appearance</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form method="POST"
                                    action="manage_offices_interface.php?office_id=<?php echo $selected_office['id']; ?>&section_id=<?php echo $active_section['id']; ?>"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="update_appearance">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="background">Upload Background Image:</label>
                                            <input type="file" name="background" id="background" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="background_color">Select Background Color:</label>
                                            <input type="color" name="background_color" id="background_color"
                                                class="form-control" value="#ffffff">
                                        </div>
                                        <div class="form-group">
                                            <label for="text_color">Select Text Color:</label>
                                            <input type="color" name="text_color" id="text_color" class="form-control"
                                                value="#000000">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary">Update Appearance</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Section Content Management Area -->
                    <?php if ($active_section) { ?>
                        <h4>Manage <?php echo ucfirst(str_replace('_', ' ', $active_section['section_type'])); ?> Section</h4>
                        <hr>
                        <?php
                        if (isset($heroMsg))
                            echo "<div class='alert alert-info'>$heroMsg</div>";
                        if (isset($galleryMsg))
                            echo "<div class='alert alert-info'>$galleryMsg</div>";
                        if (isset($docMsg))
                            echo "<div class='alert alert-info'>$docMsg</div>";
                        if (isset($aboutContentMsg))
                            echo "<div class='alert alert-info'>$aboutContentMsg</div>";
                        if (isset($serviceMsg))
                            echo "<div class='alert alert-info'>$serviceMsg</div>";
                        if (isset($contactMsg))
                            echo "<div class='alert alert-info '>$contactMsg </div>";
                        if (isset($newsMsg))
                            echo "<div class='alert alert-info'>$newsMsg</div>";
                        if (isset($annMsg))
                            echo "<div class='alert alert-info'>$annMsg</div>";
                        if (!empty($appearanceMsg))
                            echo "<div class='alert alert-info'>$appearanceMsg</div>";
                        ?>

                        <?php
                        // Render management interface based on section type
                        switch ($active_section['section_type']) {
                            case 'hero':
                                $heroData = json_decode($active_section['content'], true);
                                $headline = isset($heroData['headline']) ? $heroData['headline'] : '';
                                $tagline = isset($heroData['tagline']) ? $heroData['tagline'] : '';
                                echo "<h5>Edit Hero Section</h5>";
                                echo "<form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}'>";
                                echo "<input type='hidden' name='action' value='update_hero'>";
                                echo "<div class='form-group'><label for='headline'>Headline:</label>";
                                echo "<input type='text' name='headline' class='form-control' value='" . htmlspecialchars($headline) . "' required></div>";
                                echo "<div class='form-group'><label for='tagline'>Tagline:</label>";
                                echo "<textarea name='tagline' class='form-control' rows='3' required>" . htmlspecialchars($tagline) . "</textarea></div>";
                                echo "<button type='submit' class='btn btn-primary'>Update Hero</button></form>";
                                break;
                            case 'gallery':
                                // Button to trigger the modal for uploading a new image
                                echo "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#uploadImageModal'>Upload New Image</button>";

                                // Modal for uploading a new image
                                echo "<div class='modal fade' id='uploadImageModal' tabindex='-1' role='dialog' aria-labelledby='uploadImageModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog' role='document'>
                                        <div class='modal-content'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title' id='uploadImageModalLabel'>Upload New Image</h5>
                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div>
                                            <div class='modal-body'>
                                                <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                    <input type='hidden' name='action' value='upload_media'>
                                                    <div class='form-group'>
                                                        <label for='media_title'>Title:</label>
                                                        <input type='text' name='media_title' id='media_title' class='form-control' required>
                                                    </div>
                                                    <div class='form-group'>
                                                        <label for='media'>Select Image:</label>
                                                        <input type='file' name='media' id='media' class='form-control' required>
                                                    </div>
                                                    <div class='form-group'>
                                                        <label for='caption'>Caption:</label>
                                                        <input type='text' name='caption' id='caption' class='form-control'>
                                                    </div>
                                                    <button type='submit' class='btn btn-primary'>Upload</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>";

                                // Display gallery images in a table
                                $resImg = $conn->query("SELECT * FROM gallery_content WHERE section_id = {$active_section['id']} ORDER BY created_at DESC");
                                echo "<hr>";
                                if ($resImg && $resImg->num_rows > 0) {
                                    echo "<table class='table table-bordered'>";
                                    echo "<thead><tr><th>Image</th><th>Title</th><th>Caption</th><th>Actions</th></tr></thead><tbody>";
                                    while ($img = $resImg->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><img src='" . htmlspecialchars($img['file_path']) . "' class='media-img' style='max-width:100px;' data-toggle='modal' data-target='#imageModal-{$img['id']}'></td>";
                                        echo "<td>" . htmlspecialchars($img['title']) . "</td>";
                                        echo "<td>" . htmlspecialchars($img['caption']) . "</td>";
                                        echo "<td>
                                            <a href='" . htmlspecialchars($img['file_path']) . "' target='_blank' class='btn btn-sm btn-info'>View Fullscreen</a>
                                            <a href='#' class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editImageModal-{$img['id']}' data-id='{$img['id']}' data-title='" . htmlspecialchars($img['title']) . "' data-caption='" . htmlspecialchars($img['caption']) . "'>Edit</a>
                                            <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}&delete_media=" . $img['id'] . "' class='btn btn-sm btn-danger'> Delete</a>
                                          </td>";
                                        echo "</tr>";

                                        // Image Modal
                                        echo "<div class='modal fade' id='imageModal-{$img['id']}' tabindex='-1' role='dialog' aria-labelledby='imageModalLabel-{$img['id']}' aria-hidden='true'>
                                            <div class='modal-dialog' role='document'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='imageModalLabel-{$img['id']}'>Image Preview</h5>
                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                            <span aria-hidden='true'>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class='modal-body'>
                                                        <img src='" . htmlspecialchars($img['file_path']) . "' class='media-img'>
                                                    </div>
                                                    <div class='modal-footer'>
                                                        <a href='" . htmlspecialchars($img['file_path']) . "' download class='btn btn-secondary'>Download</a>
                                                        <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>";

                                        // Edit Modal
                                        echo "<div class='modal fade' id='editImageModal-{$img['id']}' tabindex='-1' role='dialog' aria-labelledby='editImageModalLabel-{$img['id']}' aria-hidden='true'>
                                            <div class='modal-dialog' role='document'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='editImageModalLabel-{$img['id']}'>Edit Image Details</h5>
                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                            <span aria-hidden='true'>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class='modal-body'>
                                                        <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                            <input type='hidden' name='action' value='edit_media'>
                                                            <input type='hidden' name='media_id' value='{$img['id']}'>
                                                            <div class='form-group'>
                                                                <label for='media_title'>Title:</label>
                                                                <input type='text' name='media_title' id='media_title' class='form-control' value='" . htmlspecialchars($img['title']) . "' required>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='caption'>Caption:</label>
                                                                <input type='text' name='caption' id='caption' class='form-control' value='" . htmlspecialchars($img['caption']) . "'>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='media'>Select New Image (optional):</label>
                                                                <input type='file' name='media' id='media' class='form-control'>
                                                                <small class='form-text text-muted'>Leave blank if you do not want to change the image.</small>
                                                            </div>
                                                            <button type='submit' class='btn btn-primary'>Save Changes</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                    echo "</tbody></table>";
                                } else {
                                    echo "<p>No images available.</p>";
                                }
                                break;
                            case 'about':
                                echo "<h5>Add Content to About Section</h5>";
                                echo "<button class='btn btn-primary' data-toggle='modal' data-target='#addAboutContentModal'>Add Content</button>";

                                // Add Content Modal
                                echo "<div class='modal fade' id='addAboutContentModal' tabindex='-1' role='dialog' aria-labelledby='addAboutContentModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog' role='document'>
                                        <div class='modal-content'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title' id='addAboutContentModalLabel'>Add Content to About Section</h5>
                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div>
                                            <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                <input type='hidden' name='action' value='add_about_content'>
                                                <div class='modal-body'>
                                                    <div class='form-group'>
                                                        <label for='content_type'>Content Type</label>
                                                        <select name='content_type' id='content_type' class='form-control' required>
                                                            <option value=''>Select Content Type</option>
                                                            <option value='text'>Overview / Mission and Vision</option>
                                                            <option value='image'>Organizational Chart / Citizens' Charter</option>
                                                        </select>
                                                    </div>
                                                    <div class='form-group'>
                                                        <label for='about_title'>Title:</label>
                                                        <input type='text' name='about_title' id='about_title' class='form-control' required>
                                                    </div>
                                                    <div class='form-group'>
                                                        <label for='content'>Content</label>
                                                        <textarea name='content' id='content' class='form-control' rows='3' required></textarea>
                                                    </div>
                                                    <div class='form-group' id='imageUpload' style='display:none;'>
                                                        <label for='about_image'>Upload Image:</label>
                                                        <input type='file' name='about_image' id='about_image' class='form-control'>
                                                    </div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                    <button type='submit' class='btn btn-primary'>Add Content</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>";

                                echo "<script>
                                document.getElementById('content_type').
                                .addEventListener('change', function() {
                                    var imageUpload = document.getElementById('imageUpload');
                                    if (this.value === 'image') {
                                        imageUpload.style.display = 'block';
                                    } else {
                                        imageUpload.style.display = 'none';
                                    }
                                });
                            </script>";

                                // Fetch existing about content
                                echo "<hr><h5>Existing About Content</h5>";
                                $aboutContents = $conn->query("SELECT * FROM about_content WHERE section_id = {$active_section['id']}");
                                if ($aboutContents && $aboutContents->num_rows > 0) {
                                    echo "<table class='table table-bordered'>";
                                    echo "<thead><tr><th>Content Type</th><th>Title</th><th>Content</th><th>Actions</th></tr></thead><tbody>";
                                    while ($about = $aboutContents->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($about['content_type']) . "</td>";
                                        echo "<td>" . htmlspecialchars($about['title']) . "</td>";
                                        echo "<td>";
                                        if ($about['content_type'] == 'text') {
                                            echo "<p>" . htmlspecialchars($about['content']) . "</p>";
                                        } elseif ($about['content_type'] == 'image' && !empty($about['image_path'])) {
                                            echo "<img src='" . htmlspecialchars($about['image_path']) . "' class='media-img' style='max-width:100px;' data-toggle='modal' data-target='#aboutImageModal-{$about['id']}'>";
                                            echo "<div class='modal fade' id='aboutImageModal-{$about['id']}' tabindex='-1' role='dialog' aria-labelledby='aboutImageModalLabel-{$about['id']}' aria-hidden='true'>
                                                <div class='modal-dialog' role='document'>
                                                    <div class='modal-content'>
                                                        <div class='modal-header'>
                                                            <h5 class='modal-title' id='aboutImageModalLabel-{$about['id']}'>About Image Preview</h5>
                                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                <span aria-hidden='true'>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class='modal-body'>
                                                            <img src='" . htmlspecialchars($about['image_path']) . "' class='media-img'>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <a href='" . htmlspecialchars($about['image_path']) . "' download class='btn btn-secondary'>Download</a>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                        echo "</td>";
                                        echo "<td>
                                            <button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editAboutContentModal-{$about['id']}'>Edit</button>
                                            <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}&delete_about_content=" . $about['id'] . "' class='btn btn-sm btn-danger'>Delete</a>
                                          </td>";
                                        echo "</tr>";

                                        // Edit Content Modal
                                        echo "<div class='modal fade' id='editAboutContentModal-{$about['id']}' tabindex='-1' role='dialog' aria-labelledby='editAboutContentModalLabel-{$about['id']}' aria-hidden='true'>
                                            <div class='modal-dialog' role='document'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='editAboutContentModalLabel-{$about['id']}'>Edit About Content</h5>
                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                            <span aria-hidden='true'>&times;</span>
                                                        </button>
                                                    </div>
                                                    <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                        <input type='hidden' name='action' value='edit_about_content'>
                                                        <input type='hidden' name='about_id' value='{$about['id']}'>
                                                        <div class='modal-body'>
                                                            <div class='form-group'>
                                                                <label for='content_type'>Content Type</label>
                                                                <select name='content_type' class='form-control' required>
                                                                    <option value='text' " . ($about['content_type'] == 'text' ? 'selected' : '') . ">Overview / Mission and Vision</option>
                                                                    <option value='image' " . ($about['content_type']
                                            == 'image' ? 'selected' : '') . ">Organizational Chart / Citizens' Charter</option>
                                                                </select>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='about_title'>Title:</label>
                                                                <input type='text' name='about_title' class='form-control' value='" . htmlspecialchars($about['title']) . "' required>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='content'>Content</label>
                                                                <textarea name='content' class='form-control' rows='3' required>" . htmlspecialchars($about['content']) . "</textarea>
                                                            </div>
                                                            <div class='form-group' id='imageUploadEdit-{$about['id']}' style='display:" . ($about['content_type'] == 'image' ? 'block' : 'none') . ";'>
                                                                <label for='about_image'>Upload Image:</label>
                                                                <input type='file' name='about_image' class='form-control'>
                                                            </div>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                            <button type='submit' class='btn btn-primary'>Update Content</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                    echo "</tbody></table>";
                                } else {
                                    echo "<p>No content added yet.</p>";
                                }
                                break;
                            case 'services':
                                echo "<h5>Add New Service</h5>";
                                echo '<button class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">Add Service</button>';
                                // Add Service Modal
                                echo "<div class='modal fade' id='addServiceModal' tabindex='-1' role='dialog' aria-labelledby='addServiceModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog' role='document'>
                                        <div class='modal-content'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title' id='addServiceModalLabel'>Add New Service</h5>
                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div>
                                            <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}'>
                                                <input type='hidden' name='action' value='upload_service'>
                                                <div class='modal-body'>
                                                    <div class='form-group'><label for='service_title'>Title:</label>
                                                    <input type='text' name='service_title' class='form-control' required></div>
                                                    <div class='form-group'><label for='service_description'>Description:</label>
                                                    <textarea name='service_description' class='form-control' rows='3' required></textarea></div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                    <button type='submit' class='btn btn-primary'>Add Service</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>";

                                // Display services in a table
                                $resSvc = $conn->query("SELECT * FROM services_content WHERE section_id = {$active_section['id']}");
                                echo "<hr><h5>Services</h5>";
                                if ($resSvc && $resSvc->num_rows > 0) {
                                    echo "<table class='table table-bordered'>";
                                    echo "<thead><tr><th>Title</th><th>Description</th><th>Actions</th></tr></thead><tbody>";
                                    while ($svc = $resSvc->fetch_assoc()) {
                                        list($sTitle, $sDesc) = explode("||", $svc['caption']) + array('', '');
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($sTitle) . "</td>";
                                        echo "<td>" . htmlspecialchars($sDesc) . "</td>";
                                        echo "<td>
                                            <button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editServiceModal-{$svc['id']}'>Edit</button>
                                            <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}&delete_service=" . $svc['id'] . "' class='btn btn-sm btn-danger'>Delete</a>
                                          </td>";
                                        echo "</tr>";

                                        // Edit Service Modal
                                        echo "<div class='modal fade' id='editServiceModal-{$svc['id']}' tabindex='-1' role='dialog' arial
-labelledby='editServiceModalLabel-{$svc['id']}' aria-hidden='true'>
                                            <div class='modal-dialog' role='document'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='editServiceModalLabel-{$svc['id']}'>Edit Service</h5>
                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                            <span aria-hidden='true'>&times;</span>
                                                        </button>
                                                    </div>
                                                    <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}'>
                                                        <input type='hidden' name='action' value='edit_service'>
                                                        <input type='hidden' name='service_id' value='{$svc['id']}'>
                                                        <div class='modal-body'>
                                                            <div class='form-group'><label for='service_title'>Title:</label>
                                                            <input type='text' name='service_title' class='form-control' value='" . htmlspecialchars($sTitle) . "' required></div>
                                                            <div class='form-group'><label for='service_description'>Description:</label>
                                                            <textarea name='service_description' class='form-control' rows='3' required>" . htmlspecialchars($sDesc) . "</textarea></div>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                            <button type='submit' class='btn btn-primary'>Update Service</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                    echo "</tbody></table>";
                                } else {
                                    echo "<p>No services added yet.</p>";
                                }
                                break;
                            // News Item Panel
                            case 'news':
                                echo "<h5>Add News Item</h5>";
                                echo "<button class='btn btn-primary' data-toggle='modal' data-target='#addNewsModal'>Add News</button>";

                                // Add News Modal
                                echo "<div class='modal fade' id='addNewsModal' tabindex='-1' role='dialog' aria-labelledby='addNewsModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog' role='document'>
                                        <div class='modal-content'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title' id='addNewsModalLabel'>Add News Item</h5>
                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div>
                                            <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                <input type='hidden' name='action' value='upload_news'>
                                                <div class='modal-body'>
                                                    <div class='form-group'>
                                                        <label for='news_title'>Title:</label>
                                                        <input type='text' name='news_title' class='form-control' required>
                                                    </div>
                                                    <div class='form-group'>
                                                        <label for='news_content'>Content:</label>
                                                        <textarea name='news_content' class='form-control' rows='3' required></textarea>
                                                    </div>
                                                    
                                                    <div class='form-group'>
                                                        <label>Media Type:</label>
                                                        <div class='form-check'>
                                                            <input class='form-check-input' type='radio' name='media_type' id='mediaNone' value='none' checked>
                                                            <label class='form-check-label' for='mediaNone'>None</label>
                                                        </div>
                                                        <div class='form-check'>
                                                            <input class='form-check-input' type='radio' name='media_type' id='mediaImage' value='image'>
                                                            <label class='form-check-label' for='mediaImage'>Image</label>
                                                        </div>
                                                        <div class='form-check'>
                                                            <input class='form-check-input' type='radio' name='media_type' id='mediaVideoURL' value='video_url'>
                                                            <label class='form-check-label' for='mediaVideoURL'>Video URL</label>
                                                        </div>
                                                        <div class='form-check'>
                                                            <input class='form-check-input' type='radio' name='media_type' id='mediaVideoFile' value='video_file'>
                                                            <label class='form-check-label' for='mediaVideoFile'>Video File</label>
                                                        </div>
                                                    </div>

                                                    <div class='media-input' id='imageInput' style='display: none;'>
                                                        <div class='form-group'>
                                                            <label for='news_image'>Upload Image:</label>
                                                            <input type='file' name='news_image' class='form-control' accept='image/*'>
                                                        </div>
                                                    </div>

                                                    <div class='media-input' id='videoUrlInput' style='display: none;'>
                                                        <div class='form-group'>
                                                            <label for='news_video'>Video URL:</label>
                                                            <input type='url' name='news_video' class='form-control'>
                                                        </div>
                                                    </div>

                                                    <div class='media-input' id='videoFileInput' style='display: none;'>
                                                        <div class='form-group'>
                                                            <label for='news_video_file'>Upload Video:</label>
                                                            <input type='file' name='news_video_file' class='form-control' accept='video/*'>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                    <button type='submit' class='btn btn-primary'>Add News</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const mediaTypeRadios = document.querySelectorAll('input[name=\"media_type\"]');
                                        function toggleMediaInputs() {
                                            document.querySelectorAll('.media-input').forEach(el => el.style.display = 'none');
                                            const selected = document.querySelector('input[name=\"media_type\"]:checked').value;
                                            if(selected === 'image') document.getElementById('imageInput').style.display = 'block';
                                            if(selected === 'video_url') document.getElementById('videoUrlInput').style.display = 'block';
                                            if(selected === 'video_file') document.getElementById('videoFileInput').style.display = 'block';
                                        }
                                        mediaTypeRadios.forEach(radio => radio.addEventListener('change', toggleMediaInputs));
                                        toggleMediaInputs(); // Initial call
                                    });
                                </script>";

                                // Fetch and display news items
                                $result = $conn->query("SELECT * FROM news_content WHERE section_id = {$active_section['id']} ORDER BY created_at DESC");
                                if ($result->num_rows > 0) {
                                    echo "<hr><h5>News Items</h5>";
                                    echo "<table class='table table-bordered'>";
                                    echo "<thead><tr><th>Title</th><th>Content</th><th>Media</th><th>Actions</th></tr></thead><tbody>";
                                    while ($row = $result->fetch_assoc()) {
                                        $mediaContent = '';
                                        if (!empty($row['image_path'])) {
                                            $mediaContent = "<img src='" . htmlspecialchars($row['image_path']) . "' class='media-img' style='max-width: 100px;'>";
                                        } elseif (!empty($row['video_url'])) {
                                            $mediaContent = "<a href='" . htmlspecialchars($row['video_url']) . "' target='_blank'>View Video</a>";
                                        } elseif (!empty($row['video_file_path'])) {
                                            $mediaContent = "<a href='" . htmlspecialchars($row['video_file_path']) . "' target='_blank'>View Video File</a>";
                                        }

                                        echo "<tr>
                <td>" . htmlspecialchars($row['title']) . "</td>
                <td>" . htmlspecialchars($row['content']) . "</td>
                <td>" . $mediaContent . "</td>
                <td>
                    <button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editNewsModal-{$row['id']}'>Edit</button>
                    <a href='manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}&delete_news=" . $row['id'] . "' class='btn btn-sm btn-danger'>Delete</a>
                </td>
            </tr>";

                                        // Edit News Modal
                                        echo "<div class='modal fade' id='editNewsModal-{$row['id']}' tabindex='-1' role='dialog' aria-labelledby='editNewsModalLabel-{$row['id']}' aria-hidden='true'>
                <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>Edit News Item</h5>
                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                            <input type='hidden' name='action' value='edit_news'>
                            <input type='hidden' name='news_id' value='{$row['id']}'>
                            <div class='modal-body'>
                                <div class='form-group'>
                                    <label>Title:</label>
                                    <input type='text' name='news_title' class='form-control' value='" . htmlspecialchars($row['title']) . "' required>
                                </div>
                                <div class='form-group'>
                                    <label>Content:</label>
                                    <textarea name='news_content' class='form-control' rows='3' required>" . htmlspecialchars($row['content']) . "</textarea>
                                </div>

                                <div class='form-group'>
                                    <label>Media Type:</label>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='radio' name='media_type' value='none' " . (empty($row['image_path']) && empty($row['video_url']) && empty($row['video_file_path']) ? 'checked' : '') . ">
                                        <label class='form-check-label'>None</label>
                                    </div>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='radio' name='media_type' value='image' " . (!empty($row['image_path']) ? 'checked' : '') . ">
                                        <label class='form-check-label'>Image</label>
                                    </div>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='radio' name='media_type' value='video_url' " . (!empty($row['video_url']) ? 'checked' : '') . ">
                                        <label class='form-check-label'>Video URL</label>
                                    </div>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='radio' name='media_type' value='video_file' " . (!empty($row['video_file_path']) ? 'checked' : '') . ">
                                        <label class='form-check-label'>Video File</label>
                                    </div>
                                </div>

                                <div class='media-input' id='editImageInput-{$row['id']}' style='" . (!empty($row['image_path']) ? 'display: block;' : 'display: none;') . "'>
                                    <div class='form-group'>
                                        <label>Current Image:</label>
                                        <img src='" . htmlspecialchars($row['image_path']) . "' class='media-img' style='max-width: 100px;'>
                                        <div class='form-group'>
                                            <label for='news_image'>Upload New Image (optional):</label>
                                            <input type='file' name='news_image' class='form-control' accept='image/*'>
                                        </div>
                                    </div>
                                </div>

                                <div class='media-input' id='editVideoUrlInput-{$row['id']}' style='" . (!empty($row['video_url']) ? 'display: block;' : 'display: none;') . "'>
                                    <div class='form-group'>
                                        <label>Current Video URL:</label>
                                        <a href='" . htmlspecialchars($row['video_url']) . "' target='_blank'>View Video</a>
                                        <div class='form-group'>
                                            <label for='news_video'>New Video URL (optional):</label>
                                            <input type='url' name='news_video' class='form-control' value='" . htmlspecialchars($row['video_url']) . "'>
                                        </div>
                                    </div>
                                </div>

                                <div class='media-input' id='editVideoFileInput-{$row['id']}' style='" . (!empty($row['video_file_path']) ? 'display: block;' : 'display: none;') . "'>
                                    <div class='form-group'>
                                        <label>Current Video File:</label>
                                        <a href='" . htmlspecialchars($row['video_file_path']) . "' target='_blank'>View Video File</a>
                                        <div class='form-group'>
                                            <label for='news_video_file'>Upload New Video File (optional):</label>
                                            <input type='file' name='news_video_file' class='form-control' accept='video/*'>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='modal-footer'>
                                <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                <button type='submit' class='btn btn-primary'>Update News</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const editModal = document.getElementById('editNewsModal-{$row['id']}');
                    if (editModal) {
                        const mediaTypeRadios = editModal.querySelectorAll('input[name=\"media_type\"]');
                        const imageInput = editModal.querySelector('#editImageInput-{$row['id']}');
                        const videoUrlInput = editModal.querySelector('#editVideoUrlInput-{$row['id']}');
                        const videoFileInput = editModal.querySelector('#editVideoFileInput-{$row['id']}');

                        function toggleEditMediaInputs() {
                            const selected = editModal.querySelector('input[name=\"media_type\"]:checked').value;
                            imageInput.style.display = selected === 'image' ? 'block' : 'none';
                            videoUrlInput.style.display = selected === 'video_url' ? 'block' : 'none';
                            videoFileInput.style.display = selected === 'video_file' ? 'block' : 'none';
                        }

                        mediaTypeRadios.forEach(radio => {
                            radio.addEventListener('change', toggleEditMediaInputs);
                        });

                        // Initial toggle
                        toggleEditMediaInputs();
                    }
                });
            </script>
        </div>
    </div>";
                                    }
                                }
                                break;
                                case 'announcements':
                                    echo "<h5>Add Announcement</h5>";
                                    echo "<button class='btn btn-primary' data-toggle='modal' data-target='#addAnnouncementModal'>Add Announcement</button>";
                                
                                    // Add Announcement Modal
                                    echo "<div class='modal fade' id='addAnnouncementModal' tabindex='-1' role='dialog' aria-labelledby='addAnnouncementModalLabel' aria-hidden='true'>
                                            <div class='modal-dialog' role='document'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='addAnnouncementModalLabel'>Add Announcement</h5>
                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                            <span aria-hidden='true'>&times;</span>
                                                        </button>
                                                    </div>
                                                    <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                        <input type='hidden' name='action' value='upload_announcement'>
                                                        <div class='modal-body'>
                                                            <div class='form-group'>
                                                                <label for='announcementTitle'>Title:</label>
                                                                <input type='text' name='title' class='form-control' id='announcementTitle' placeholder='Enter announcement title' required>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='announcementCaption'>Caption:</label>
                                                                <textarea name='caption' class='form-control' id='announcementCaption' placeholder='Enter announcement caption' rows='3' required></textarea>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='announcementImage'>Upload Image:</label>
                                                                <input type='file' name='announcement_image' class='form-control' id='announcementImage' accept='image/*'>
                                                            </div>
                                                            <div class='form-group'>
                                                                <label for='announcementBgColor'>Background Color:</label>
                                                                <input type='color' name='background_color' class='form-control' id='announcementBgColor' value='#ff6f61'>
                                                            </div>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                            <button type='submit' class='btn btn-primary'>Add Announcement</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>";
                                
                                    // Fetch existing announcements
                                    $announcements = $conn->query("SELECT * FROM announcements_content WHERE section_id = {$active_section['id']} ORDER BY created_at DESC");
                                    if ($announcements && $announcements->num_rows > 0) {
                                        echo "<hr><h5>Existing Announcements</h5>";
                                        echo "<table class='table table-bordered'>";
                                        echo "<thead><tr> <th>Title</th> <th>Caption</th> <th>Background Color</th> <th>Image</th> <th>Actions</th> </tr></thead><tbody>";
                                        while ($announcement = $announcements->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($announcement['title']) . "</td>";
                                            echo "<td>" . htmlspecialchars($announcement['caption']) . "</td>";
                                            echo "<td> <div style='width: 30px; height: 30px; background:" . htmlspecialchars($announcement['background_color'] ?? '#ff6f61') . "; border: 1px solid #ccc;'></div> </td>";
                                            
                                            // Display the uploaded image if it exists
                                            echo "<td>";
                                            if (!empty($announcement['image_path'])) {
                                                echo "<img src='" . htmlspecialchars($announcement['image_path']) . "' alt='Announcement Image' style='width: 50px; height: auto;'>";
                                            } else {
                                                echo "No image uploaded";
                                            }
                                            echo "</td>";
                                
                                            echo "<td> 
                                                    <button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editAnnouncementModal-{$announcement['id']}'>Edit</button> 
                                                    <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}&delete_announcement=" . $announcement['id'] . "' class='btn btn-sm btn-danger'>Delete</a> 
                                                  </td>";
                                            echo "</tr>";
                                
                                            // Edit Announcement Modal
                                            echo "<div class='modal fade' id='editAnnouncementModal-{$announcement['id']}' tabindex='-1' role='dialog' aria-labelledby='editAnnouncementModalLabel-{$announcement['id']}' aria-hidden='true'>
                                                    <div class='modal-dialog' role='document'>
                                                        <div class='modal-content'>
                                                            <div class='modal-header'>
                                                                <h5 class='modal-title' id='editAnnouncementModalLabel-{$announcement['id']}'>Edit Announcement</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                    <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                            </div>
                                                            <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                                <input type='hidden' name='action' value='edit_announcement'>
                                                                <input type='hidden' name='announcement_id' value='{$announcement['id']}'>
                                                                <div class='modal-body'>
                                                                    <div class='form-group'>
                                                                        <label for='announcementTitle-{$announcement['id']}'>Title:</label>
                                                                        <input type='text' name='title' class='form-control' id='announcementTitle-{$announcement['id']}' value='" . htmlspecialchars($announcement['title']) . "' required>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='announcementCaption-{$announcement['id']}'>Caption:</label>
                                                                        <textarea name='caption' class='form-control' id='announcementCaption-{$announcement['id']}' rows='3' required>" . htmlspecialchars($announcement['caption']) . "</textarea>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='announcementBgColor-{$announcement['id']}'>Background Color:</label>
                                                                        <input type='color' name='background_color' class='form-control' id='announcementBgColor-{$announcement['id']}' value='" . htmlspecialchars($announcement['background_color'] ?? '#ff6f61') . "'>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='announcementImage-{$announcement['id']}'>Upload New Image:</label>
                                                                        <input type='file' name='announcement_image' class='form-control' id='announcementImage-{$announcement['id']}' accept='image/*'>
                                                                    </div>
                                                                </div>
                                                                <div class='modal-footer'>
                                                                    <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                                    <button type='submit' class='btn btn-primary'>Update Announcement</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>";
                                        }
                                        echo "</tbody></table>";
                                    } else {
                                        echo "<p>No announcements added yet.</p>";
                                    }
                                    break;

                                    case 'documents':
                                        echo "<h5>Add Downloadable Document</h5>";
                                        echo "<form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>";
                                        echo "<input type='hidden' name='action' value='upload_document'>";
                                        echo "<div class='form-group'><label for='doc_title'>Document Title:</label>";
                                        echo "<input type='text' name='doc_title' class='form-control' required></div>";
                                        echo "<div class='form-group'><label for='document'>Select Document:</label>";
                                        echo "<input type='file' name='document' class='form-control' accept='.pdf,.doc,.docx,.txt,.xlsx,.pptx' required></div>";
                                        echo "<button type='submit' class='btn btn-primary'>Upload Document</button></form>";
                                    
                                        // Fetch existing documents
                                        $documents = $conn->query("SELECT * FROM documents WHERE section_id = {$active_section['id']}");
                                        if ($documents && $documents->num_rows > 0) {
                                            echo "<hr><h5>Existing Documents</h5>";
                                            echo "<table class='table table-bordered'>";
                                            echo "<thead><tr><th>Title</th><th>Actions</th></tr></thead><tbody>";
                                    
                                            while ($doc = $documents->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($doc['title']) . "</td>";
                                                echo "<td>
                                                        <button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editDocumentModal-{$doc['id']}'>Edit</button>
                                                        <a href='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}&delete_doc=" . $doc['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this document?\");'>Delete</a>
                                                      </td>";
                                                echo "</tr>";
                                    
                                                // Edit Document Modal
                                                echo "<div class='modal fade' id='editDocumentModal-{$doc['id']}' tabindex='-1' role='dialog' aria-labelledby='editDocumentModalLabel-{$doc['id']}' aria-hidden='true'>
                                                        <div class='modal-dialog' role='document'>
                                                            <div class='modal-content'>
                                                                <div class='modal-header'>
                                                                    <h5 class='modal-title' id='editDocumentModalLabel-{$doc['id']}'>Edit Document</h5>
                                                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                        <span aria-hidden='true'>&times;</span>
                                                                    </button>
                                                                </div>
                                                                <form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' enctype='multipart/form-data'>
                                                                    <input type='hidden' name='action' value='edit_document'>
                                                                    <input type='hidden' name='document_id' value='{$doc['id']}'>
                                                                    <div class='modal-body'>
                                                                        <div class='form-group'>
                                                                            <label for='doc_title'>Document Title:</label>
                                                                            <input type='text' name='doc_title' id='doc_title' class='form-control' value='" . htmlspecialchars($doc['title']) . "' required>
                                                                        </div>
                                                                        <div class='form-group'>
                                                                            <label>Current Document:</label>
                                                                            <a href='" . htmlspecialchars($doc['file_path']) . "' target='_blank'>View Current Document</a>
                                                                        </div>
                                                                        <div class='form-group'>
                                                                            <label for='document'>Upload New Document (optional):</label>
                                                                            <input type='file' name='document' id='document' class='form-control' accept='.pdf,.doc,.docx,.txt,.xlsx,.pptx'>
                                                                        </div>
                                                                    </div>
                                                                    <div class='modal-footer'>
                                                                        <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                                        <button type='submit' class='btn btn-primary'>Update Document</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>";
                                            }
                                            echo "</tbody></table>";
                                        } else {
                                            echo "<p>No documents added yet.</p>";
                                        }
                                        break;
                                    
                            case 'contact':
                                // Display the contact information form
                                echo "<h5>Contact Information</h5>";

                                // Check if active_section and its content are set
                                $email = isset($active_section['content']['email']) ? htmlspecialchars($active_section['content']['email']) : '';
                                $phone = isset($active_section['content']['phone']) ? htmlspecialchars($active_section['content']['phone']) : '';

                                // Display success/error message if set
                                if (isset($contactMsg) && !empty($contactMsg)) {
                                    echo "<div class='alert alert-success'>$contactMsg</div>";
                                    // Clear the message after displaying it to prevent duplication
                                    unset($contactMsg);
                                }

                                echo "<form method='POST' action='manage_offices_interface.php?office_id={$selected_office['id']}&section_id={$active_section['id']}' novalidate>
                                    <input type='hidden' name='action' value='update_contact'>
                                    <div class='form-row'>
                                        <div class='form-group col-md-6'>
                                            <label for='contact_email'>Email:</label>
                                            <input type='email' name='contact_email' id='contact_email' class='form-control' value='$email' placeholder='Enter your email' required>
                                            <div class='invalid-feedback'>Please enter a valid email address.</div>
                                        </div>
                                        <div class='form-group col-md-6'>
                                            <label for='contact_phone'>Phone:</label>
                                            <input type='tel' name='contact_phone' id='contact_phone' class='form-control' value='$phone' placeholder='Enter your phone number' required pattern='[0-9]{10}'>
                                            <div class='invalid-feedback'>Please enter a valid phone number (10 digits).</div>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <button type='submit' class='btn btn-primary'>Update Contact Info</button>
                                        <button type='reset' class='btn btn-secondary'>Reset</button>
                                    </div>
                                  </form>";

                                // Add JavaScript for form validation
                                echo "<script>
                                    (function() {
                                        'use strict';
                                        window.addEventListener('load', function() {
                                            var forms = document.getElementsByClassName('needs-validation');
                                            for (var i = 0; i < forms.length; i++) {
                                                forms[i].addEventListener('submit', function(event) {
                                                    if (this.checkValidity() === false) {
                                                        event.preventDefault();
                                                        event.stopPropagation();
                                                    }
                                                    this.classList.add('was-validated');
                                                }, false);
                                            }
                                        }, false);
                                    })();
                                  </script>";
                                break;
                            default:
                                echo "<p>No management functionality for this section type.</p>";
                        }
                        ?>
                    <?php } ?>
                <?php } else { ?>
                    <h3>Please select an office to manage.</h3>
                <?php } ?>
            </div> <!-- End Main Column -->
        </div> <!-- End Row -->
    </div> <!-- End Container -->
</body>

</html>