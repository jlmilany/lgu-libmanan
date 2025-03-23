<?php
// manage_offices_backend.php – Backend operations for the office management system
require_once 'config.php';

// Function to ensure upload directories exist
function ensureUploadDir($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Ensure all necessary upload directories exist
ensureUploadDir('uploads/backgrounds/');
ensureUploadDir('uploads/gallery/');
ensureUploadDir('uploads/documents/');
ensureUploadDir('uploads/about/');
ensureUploadDir('uploads/logos/');
ensureUploadDir('uploads/images/');
ensureUploadDir('uploads/videos/');

if (isset($_GET['delete_section'])) {
    $delSecId = intval($_GET['delete_section']);
    $officeId = intval($_GET['office_id']);

    // Step 1: Delete related records in all dependent tables
    $conn->query("DELETE FROM gallery_content WHERE section_id = $delSecId");
    $conn->query("DELETE FROM announcements_content WHERE section_id = $delSecId");

    // Step 2: Delete the section itself
    $conn->query("DELETE FROM sections WHERE id = $delSecId");

    header("Location: manage_offices_interface.php?office_id=$officeId");
    exit();
}

// 2. PROCESS: Update Section Appearance
$appearanceMsg = "";
if (isset($_GET['section_id']) && isset($_POST['action']) && $_POST['action'] == 'update_appearance') {
    $secId = intval($_GET['section_id']);
    $msgs = [];

    // Process Background image upload (optional)
    if (isset($_FILES['background']) && $_FILES['background']['error'] == 0) {
        $bgFile = time() . '_' . basename($_FILES['background']['name']);
        $targetBg = 'uploads/backgrounds/' . $bgFile;
        if (move_uploaded_file($_FILES['background']['tmp_name'], $targetBg)) {
            $conn->query("UPDATE sections SET background_path = '$targetBg', background_color = NULL WHERE id = $secId");
            $msgs[] = "Background image updated.";
        } else {
            $msgs[] = "Error uploading background image.";
        }
    } elseif (isset($_POST['background_color']) && !empty($_POST['background_color'])) {
        $bgColor = $conn->real_escape_string($_POST['background_color']);
        $conn->query("UPDATE sections SET background_color = '$bgColor', background_path = NULL WHERE id = $secId");
        $msgs[] = "Background color updated.";
    }

    // Process Text Color selection
    if (isset($_POST['text_color']) && !empty($_POST['text_color'])) {
        $txtColor = $conn->real_escape_string($_POST['text_color']);
        $conn->query("UPDATE sections SET text_color = '$txtColor' WHERE id = $secId");
        $msgs[] = "Text color updated.";
    }
    $appearanceMsg = implode(" ", $msgs);
}

// 3. PROCESS: Section-Type Specific Management
$active_section = null;
if (isset($_GET['section_id'])) {
    $secId = intval($_GET['section_id']);
    $resSec = $conn->query("SELECT * FROM sections WHERE id = $secId");
    if ($resSec && $resSec->num_rows > 0) {
        $active_section = $resSec->fetch_assoc();
    }
}

// Check and create the announcements_content table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS announcements_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        caption TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (section_id) REFERENCES sections(id)
    )
");

// Continue with section management
if ($active_section) {
    $type = $active_section['section_type'];

    // (A) HERO Section – update headline & tagline (JSON)
    if ($type == 'hero' && isset($_POST['action']) && $_POST['action'] == 'update_hero') {
        $headline = $conn->real_escape_string($_POST['headline']);
        $tagline = $conn->real_escape_string($_POST['tagline']);

        $data = json_encode(['headline' => $headline, 'tagline' => $tagline]);
        $escapedData = $conn->real_escape_string($data); // Escape the JSON string
        $sql = "UPDATE sections SET content = '$escapedData' WHERE id = {$active_section['id']}";
        $heroMsg = ($conn->query($sql) === TRUE) ? "Hero section updated." : "Error: " . $conn->error;
        $active_section['content'] = $data;
    }
    // (B) GALLERY – upload image; delete image if requested
    elseif ($type == 'gallery') {
        // (1) Handle image upload
        if (isset($_POST['action']) && $_POST['action'] == 'upload_media') {
            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                $filename = time() . '_' . basename($_FILES['media']['name']);
                $targetFile = 'uploads/gallery/' . $filename;
                $caption = $conn->real_escape_string($_POST['caption']);
                if (move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                    $title = $conn->real_escape_string($_POST['media_title']);
                    $conn->query("INSERT INTO gallery_content (section_id, file_path, caption, title) VALUES ({$active_section['id']}, '$targetFile', '$caption', '$title')");
                    $galleryMsg = "Image uploaded.";
                } else {
                    $galleryMsg = "Error uploading image.";
                }
            } else {
                $galleryMsg = "No file selected or file upload error.";
            }
        }

        // (2) Handle image deletion
        if (isset($_GET['delete_media'])) {
            $mediaId = intval($_GET['delete_media']);
            $r = $conn->query("SELECT file_path FROM gallery_content WHERE id = $mediaId AND section_id = {$active_section['id']}");
            if ($r && $r->num_rows > 0) {
                $d = $r->fetch_assoc();
                if (file_exists($d['file_path']))
                    unlink($d['file_path']);
                $conn->query("DELETE FROM gallery_content WHERE id = $mediaId");
            }
            header("Location: manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}");
            exit();
        }

        // (3) Handle image editing
        if (isset($_POST['action']) && $_POST['action'] == 'edit_media') {
            $media_id = intval($_POST['media_id']);
            $media_title = $conn->real_escape_string($_POST['media_title']);
            $caption = $conn->real_escape_string($_POST['caption']);

            // Check if a new image is uploaded
            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                // Get the current file path
                $r = $conn->query("SELECT file_path FROM gallery_content WHERE id = $media_id");
                if ($r && $r->num_rows > 0) {
                    $d = $r->fetch_assoc();
                    // Delete the old image file
                    if (file_exists($d['file_path']))
                        unlink($d['file_path']);

                    // Upload the new image
                    $filename = time() . '_' . basename($_FILES['media']['name']);
                    $targetFile = 'uploads/gallery/' . $filename;
                    if (move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                        // Update the database with the new file path
                        $stmt = $conn->prepare("UPDATE gallery_content SET title = ?, caption = ?, file_path = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $media_title, $caption, $targetFile, $media_id);
                        $stmt->execute();
                    } else {
                        $galleryMsg = "Error uploading new image.";
                    }
                }
            } else {
                // If no new image is uploaded, just update the title and caption
                $stmt = $conn->prepare("UPDATE gallery_content SET title = ?, caption = ? WHERE id = ?");
                $stmt->bind_param("ssi", $media_title, $caption, $media_id);
                $stmt->execute();
            }
            $galleryMsg = "Image details updated successfully.";
        }
    }
 // (C) DOCUMENTS – upload document; delete if requested
elseif ($type == 'documents') {
    if (isset($_POST['action']) && $_POST['action'] == 'upload_document') {
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $filename = time() . '_' . basename($_FILES['document']['name']);
            $targetFile = 'uploads/documents/' . $filename;
            $allowed = ['pdf', 'doc', 'docx', 'txt', 'xlsx', 'pptx'];
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $docMsg = "Invalid file type.";
            } else {
                if (move_uploaded_file($_FILES['document']['tmp_name'], $targetFile)) {
                    $doc_title = $conn->real_escape_string($_POST['doc_title']);
                    // Insert into the documents table with office_id and section_id
                    $conn->query("INSERT INTO documents (office_id, section_id, file_path, title) VALUES ({$active_section['office_id']}, {$active_section['id']}, '$targetFile', '$doc_title')");
                    $docMsg = "Document uploaded.";
                } else {
                    $docMsg = "Error uploading document.";
                }
            }
        } else {
            $docMsg = "No file selected or error.";
        }
    }

    if (isset($_GET['delete_doc'])) {
        $docId = intval($_GET['delete_doc']);
        // Fetch the document record based on both office_id and section_id
        $r = $conn->query("SELECT file_path FROM documents WHERE id = $docId AND section_id = {$active_section['id']} AND office_id = {$active_section['office_id']}");
        if ($r && $r->num_rows > 0) {
            $d = $r->fetch_assoc();
            if (file_exists($d['file_path'])) {
                unlink($d['file_path']); // Delete the file from the server
            }
            $conn->query("DELETE FROM documents WHERE id = $docId AND section_id = {$active_section['id']} AND office_id = {$active_section['office_id']}");
        }
        header("Location: manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}");
        exit();
    }
}

    // (D) ABOUT – update text content (stored in content)
    elseif ($type == 'about') {
        // Handle adding new content
        if (isset($_POST['action']) && $_POST['action'] == 'add_about_content') {
            $contentType = $conn->real_escape_string($_POST['content_type']);
            $content = $conn->real_escape_string($_POST['content']);
            $imagePath = null;

            // Handle image upload if content type is image
            if ($contentType == 'image' && isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
                $imageFile = time() . '_' . basename($_FILES['about_image']['name']);
                $targetImage = 'uploads/about/' . $imageFile;
                if (move_uploaded_file($_FILES['about_image']['tmp_name'], $targetImage)) {
                    $imagePath = $targetImage;
                } else {
                    $imagePath = null; // Handle error
                }
            }

            // Insert into about_content table
            $title = $conn->real_escape_string($_POST['about_title']);
            $conn->query("INSERT INTO about_content (section_id, content_type, content, image_path, title) VALUES ({$active_section['id']}, '$contentType', '$content', '$imagePath', '$title')");
            $aboutContentMsg = "Content added to About section.";
        }

        // Fetch existing about content
        $aboutContents = $conn->query("SELECT * FROM about_content WHERE section_id = {$active_section['id']}");

        // Handle editing about content
        if (isset($_POST['action']) && $_POST['action'] == 'edit_about_content') {
            $aboutId = intval($_POST['about_id']);
            $contentType = $conn->real_escape_string($_POST['content_type']);
            $content = $conn->real_escape_string($_POST['content']);
            $imagePath = null;

            // Handle image upload if content type is image
            if ($contentType == 'image' && isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
                $imageFile = time() . '_' . basename($_FILES['about_image']['name']);
                $targetImage = 'uploads/about/' . $imageFile;
                if (move_uploaded_file($_FILES['about_image']['tmp_name'], $targetImage)) {
                    $imagePath = $targetImage;
                } else {
                    $imagePath = null; // Handle error
                }
            }

            // Update about content
            $title = $conn->real_escape_string($_POST['about_title']);
            $conn->query("UPDATE about_content SET content_type = '$contentType', content = '$content', image_path = '$imagePath', title = '$title' WHERE id = $aboutId");
            $aboutContentMsg = "Content updated in About section.";
        }

        // Handle deleting about content
if (isset($_GET['delete_about_content'])) {
    $aboutId = intval($_GET['delete_about_content']);
    
    // Fetch the existing about content to get the image path if it exists
    $r = $conn->query("SELECT image_path FROM about_content WHERE id = $aboutId AND section_id = {$active_section['id']}");
    if ($r && $r->num_rows > 0) {
        $d = $r->fetch_assoc();
        // Delete the image file if it exists
        if (!empty($d['image_path']) && file_exists($d['image_path'])) {
            unlink($d['image_path']);
        }
        // Delete the about content from the database
        $conn->query("DELETE FROM about_content WHERE id = $aboutId AND section_id = {$active_section['id']}");
    }
    header("Location: manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}");
    exit();
}
    }
   // (E) SERVICES – add a service (each stored as "title||description")
elseif ($type == 'services') {
    if (isset($_POST['action']) && $_POST['action'] == 'upload_service') {
        $service_title = $conn->real_escape_string($_POST['service_title']);
        $service_desc = $conn->real_escape_string($_POST['service_description']);
        
        // Count the number of words in the description
        $wordCount = str_word_count($service_desc);
        
        // Check if the word count exceeds 30
        if ($wordCount > 30) {
            $serviceMsg = "Error: Description cannot exceed 30 words.";
        } else {
            $combined = $service_title . "||" . $service_desc;
            $conn->query("INSERT INTO services_content (section_id, caption, title) VALUES ({$active_section['id']}, '$combined', '$service_title')");
            $serviceMsg = "Service added.";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit_service') {
        $serviceId = intval($_POST['service_id']);
        $service_title = $conn->real_escape_string($_POST['service_title']);
        $service_desc = $conn->real_escape_string($_POST['service_description']);
        
        // Check word count
        $wordCount = str_word_count($service_desc);
        if ($wordCount > 30) {
            $serviceMsg = "Error: Description cannot exceed 30 words.";
        } else {
            $combined = $service_title . "||" . $service_desc;
            $conn->query("UPDATE services_content SET caption = '$combined', title = '$service_title' WHERE id = $serviceId");
            $serviceMsg = "Service updated.";
        }
    }

    // Handle deleting a service
if (isset($_GET['delete_service'])) {
    $svcId = intval($_GET['delete_service']);
    // Delete the service from the database
    $conn->query("DELETE FROM services_content WHERE id = $svcId AND section_id = {$active_section['id']}");
    header("Location: manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}");
    exit();
}
}
    // (F) CONTACT – update JSON data (email and phone)
    elseif ($type == 'contact' && isset($_POST['action']) && $_POST['action'] == 'update_contact') {
        $email = $conn->real_escape_string($_POST['contact_email']);
        $phone = $conn->real_escape_string($_POST['contact_phone']);
        $data = json_encode(['email' => $email, 'phone' => $phone]);
        $conn->query("UPDATE sections SET content = '$data' WHERE id = {$active_section['id']}");
        $contactMsg = "Contact info updated.";
        $active_section['content'] = $data;
    }
    
    elseif ($type == 'news') {
        // Upload a new news item
        if (isset($_POST['action']) && $_POST['action'] == 'upload_news') {
            $nTitle  = $conn->real_escape_string($_POST['news_title']);
            $nContent = $conn->real_escape_string($_POST['news_content']);
            $mediaType = $_POST['media_type'];
            $videoURL = '';
            $imagePath = '';
            $videoFilePath = '';
    
            if ($mediaType == 'image' && isset($_FILES['news_image']) && $_FILES['news_image']['error'] == UPLOAD_ERR_OK) {
                $imageTmpPath = $_FILES['news_image']['tmp_name'];
                $imageName = basename($_FILES['news_image']['name']);
                $imagePath = "uploads/images/" . $imageName;
                move_uploaded_file($imageTmpPath, $imagePath);
            } elseif ($mediaType == 'video_url' && !empty($_POST['news_video'])) {
                $videoURL = $conn->real_escape_string($_POST['news_video']);
            } elseif ($mediaType == 'video_file' && isset($_FILES['news_video_file']) && $_FILES['news_video_file']['error'] == UPLOAD_ERR_OK) {
                $videoTmpPath = $_FILES['news_video_file']['tmp_name'];
                $videoName = basename($_FILES['news_video_file']['name']);
                $videoFilePath = "uploads/videos/" . $videoName;
                move_uploaded_file($videoTmpPath, $videoFilePath);
            }
    
            $insertQuery = "INSERT INTO news_content (office_id, section_id, title, created_at, content, video_url, image_path, video_file_path) 
                            VALUES ({$active_section['office_id']}, {$active_section['id']}, '$nTitle', NOW(), '$nContent', '$videoURL', '$imagePath', '$videoFilePath')";
            $conn->query($insertQuery);
            $newsMsg = "News item added.";
        }
    
        // Edit an existing news item
        if (isset($_POST['action']) && $_POST['action'] == 'edit_news') {
            $newsId = intval($_POST['news_id']);
            $nTitle = $conn->real_escape_string($_POST['news_title']);
            $nContent = $conn->real_escape_string($_POST['news_content']);
            $mediaType = $_POST['media_type'];
    
            // Retrieve existing data with proper office and section filtering
            $existingNews = $conn->query("SELECT image_path, video_url, video_file_path 
                                          FROM news_content 
                                          WHERE id = $newsId 
                                          AND section_id = {$active_section['id']}
                                          AND office_id = {$active_section['office_id']}");
            $existingData = $existingNews->fetch_assoc();
    
            $imagePath = $existingData['image_path'];
            $videoURL = $existingData['video_url'];
            $videoFilePath = $existingData['video_file_path'];
    
            if ($mediaType == 'image' && isset($_FILES['news_image']) && $_FILES['news_image']['error'] == UPLOAD_ERR_OK) {
                if (!empty($existingData['image_path']) && file_exists($existingData['image_path'])) {
                    unlink($existingData['image_path']);
                }
                $imageTmpPath = $_FILES['news_image']['tmp_name'];
                $imageName = basename($_FILES['news_image']['name']);
                $imagePath = "uploads/images/" . $imageName;
                move_uploaded_file($imageTmpPath, $imagePath);
                // Reset alternative media types
                $videoURL = '';
                $videoFilePath = '';
            } elseif ($mediaType == 'video_url' && !empty($_POST['news_video'])) {
                $videoURL = $conn->real_escape_string($_POST['news_video']);
                $imagePath = '';
                $videoFilePath = '';
            } elseif ($mediaType == 'video_file' && isset($_FILES['news_video_file']) && $_FILES['news_video_file']['error'] == UPLOAD_ERR_OK) {
                if (!empty($existingData['video_file_path']) && file_exists($existingData['video_file_path'])) {
                    unlink($existingData['video_file_path']);
                }
                $videoTmpPath = $_FILES['news_video_file']['tmp_name'];
                $videoName = basename($_FILES['news_video_file']['name']);
                $videoFilePath = "uploads/videos/" . $videoName;
                move_uploaded_file($videoTmpPath, $videoFilePath);
                $imagePath = '';
                $videoURL = '';
            }
    
            $updateQuery = "UPDATE news_content 
                            SET title = '$nTitle', content = '$nContent', video_url = '$videoURL', image_path = '$imagePath', video_file_path = '$videoFilePath' 
                            WHERE id = $newsId 
                            AND section_id = {$active_section['id']} 
                            AND office_id = {$active_section['office_id']}";
            $conn->query($updateQuery);
            $newsMsg = "News item updated.";
        }
    
        // Delete a news item
        if (isset($_GET['delete_news'])) {
            $newsId = intval($_GET['delete_news']);
            $existingNews = $conn->query("SELECT image_path, video_file_path 
                                          FROM news_content 
                                          WHERE id = $newsId 
                                          AND section_id = {$active_section['id']}
                                          AND office_id = {$active_section['office_id']}");
            $existingData = $existingNews->fetch_assoc();
    
            if (!empty($existingData['image_path']) && file_exists($existingData['image_path'])) {
                unlink($existingData['image_path']);
            }
            if (!empty($existingData['video_file_path']) && file_exists($existingData['video_file_path'])) {
                unlink($existingData['video_file_path']);
            }
    
            $conn->query("DELETE FROM news_content 
                          WHERE id = $newsId 
                          AND section_id = {$active_section['id']}
                          AND office_id = {$active_section['office_id']}");
            $newsMsg = "News item deleted.";
        }
    }
    

    // (H) ANNOUNCEMENTS – add announcement (simple text stored in caption)
    elseif ($type == 'announcements') {
        // Add a new announcement
        if (isset($_POST['action']) && $_POST['action'] == 'upload_announcement') {
            $title = $conn->real_escape_string($_POST['title']);
            $caption = $conn->real_escape_string($_POST['caption']);
            $background_color = $conn->real_escape_string($_POST['background_color']);
            // Default image_path to empty string so it isn’t null.
            $image_path = "";
    
            // Handle image upload if a file is provided
            if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/"; // Ensure this directory exists and is writable
                $filename = basename($_FILES['announcement_image']['name']);
                // Optionally prepend a timestamp or unique ID to avoid collisions
                $uniqueFilename = time() . '_' . $filename;
                $target_file = $target_dir . $uniqueFilename;
                if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file; // Use the uploaded file's path
                } else {
                    // Optionally log the error or set a default image placeholder
                    // $image_path remains ""
                }
            }
    
            // Build the INSERT query. Since image_path is now always a non-null string,
            // we safely insert it.
            $insertQuery = "INSERT INTO announcements_content 
                                (office_id, section_id, title, caption, background_color, image_path) 
                            VALUES 
                                ({$active_section['office_id']}, {$active_section['id']}, '$title', '$caption', '$background_color', '$image_path')";
    
            if (!$conn->query($insertQuery)) {
                // Handle query error (logging, etc.)
                $annMsg = "Error adding announcement: " . $conn->error;
            } else {
                $annMsg = "Announcement added.";
            }
        }
    
        // Edit an existing announcement
        if (isset($_POST['action']) && $_POST['action'] == 'edit_announcement') {
            $announcementId = intval($_POST['announcement_id']);
            $title = $conn->real_escape_string($_POST['title']);
            $caption = $conn->real_escape_string($_POST['caption']);
            $background_color = $conn->real_escape_string($_POST['background_color']);
            // For updates, do not change the image_path unless a new file is uploaded.
            $image_path = ""; 
    
            // Handle image upload if a new image is provided
            if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/"; // Ensure this directory exists and is writable
                $filename = basename($_FILES['announcement_image']['name']);
                $uniqueFilename = time() . '_' . $filename;
                $target_file = $target_dir . $uniqueFilename;
                if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    // Optionally log the error; do not attempt to update the image_path value
                    $image_path = "";
                }
            }
    
            $updateQuery = "UPDATE announcements_content 
                            SET title = '$title', caption = '$caption', background_color = '$background_color'";
            if ($image_path !== "") {
                $updateQuery .= ", image_path = '$image_path'";
            }
            $updateQuery .= " WHERE id = $announcementId 
                              AND section_id = {$active_section['id']} 
                              AND office_id = {$active_section['office_id']}";
    
            if (!$conn->query($updateQuery)) {
                $annMsg = "Error updating announcement: " . $conn->error;
            } else {
                $annMsg = "Announcement updated.";
            }
        }
    
        // Delete an announcement
        if (isset($_GET['delete_announcement'])) {
            $annId = intval($_GET['delete_announcement']);
            if (!$conn->query("DELETE FROM announcements_content 
                          WHERE id = $annId 
                          AND section_id = {$active_section['id']}
                          AND office_id = {$active_section['office_id']}")) {
                // Optionally log deletion error
            }
            header("Location: manage_offices_interface.php?office_id={$active_section['office_id']}&section_id={$active_section['id']}");
            exit();
        }
    }
}

// 4. PROCESS: Add New Section (for any type)
if (isset($_POST['action']) && $_POST['action'] == 'add_section') {
    $newType = $conn->real_escape_string($_POST['section_type']);
    $office_id = intval($_POST['office_id']);
    // Insert new section (ordering will be by created_at)
    $conn->query("INSERT INTO sections (office_id, section_type) VALUES ($office_id, '$newType')");
    $sectionAddMsg = "New section added.";
}

// 5. PROCESS: Upload Office Logo
if (isset($_POST['action']) && $_POST['action'] == 'upload_logo') {
    $officeId = intval($_POST['office_id']);
    if (isset($_FILES['office_logo']) && $_FILES['office_logo']['error'] == 0) {
        $logoFile = time() . '_' . basename($_FILES['office_logo']['name']);
        $targetLogo = 'uploads/logos/' . $logoFile;
        if (move_uploaded_file($_FILES['office_logo']['tmp_name'], $targetLogo)) {
            $conn->query("UPDATE offices SET logo_path = '$targetLogo' WHERE id = $officeId");
            $logoMsg = "Office logo updated.";
        } else {
            $logoMsg = "Error uploading office logo.";
        }
    }
}

// 6. FETCH: List All Offices & Selected Office
$officesRes = $conn->query("SELECT * FROM offices");
$selected_office = null;
$officeParam = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;
if ($officeParam) {
    $resOffice = $conn->query("SELECT * FROM offices WHERE id = $officeParam");
    if ($resOffice && $resOffice->num_rows > 0) {
        $selected_office = $resOffice->fetch_assoc();
    }
}
?>