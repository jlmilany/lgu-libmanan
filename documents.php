<?php
require_once __DIR__ . '/../config.php'; // Ensure this file exists and connects to your database

if (!$conn) {
    die("Database connection error.");
}

// Get section ID and office ID from request
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;
$office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : null;

// Fetch section details if section_id is provided
$section = [];
if ($section_id) {
    $section_query = $conn->prepare("SELECT * FROM sections WHERE id = ?");
    $section_query->bind_param("i", $section_id);
    $section_query->execute();
    $section_result = $section_query->get_result();
    $section = $section_result->fetch_assoc();

    if (!$section) {
        die("<div class='alert alert-danger'>Section not found.</div>");
    }
}

// Fetch documents (filtered by section and office)
$documents = [];
if ($section_id && $office_id) {
    $doc_query = $conn->prepare("SELECT * FROM documents WHERE section_id = ? AND office_id = ?");
    $doc_query->bind_param("ii", $section_id, $office_id);
} else {
    $doc_query = $conn->prepare("SELECT * FROM documents WHERE office_id = ?");
    $doc_query->bind_param("i", $office_id);
}

if (!$doc_query->execute()) {
    die("<div class='alert alert-danger'>Error fetching documents: " . $conn->error . "</div>");
}

$doc_result = $doc_query->get_result();
while ($row = $doc_result->fetch_assoc()) {
    $documents[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .document-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .document-card:hover {
            transform: scale(1.05);
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        /* Enhanced Search Bar Styles */
        .input-group.search-bar {
            max-width: 400px;
            margin-bottom: 20px;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .input-group.search-bar .form-control {
            border: none;
            padding: 12px 15px;
        }
        .input-group.search-bar .input-group-text {
            background-color: #ffffff;
            border: none;
        }
        .input-group.search-bar .form-control:focus {
            box-shadow: none;
            border-color: #86b7fe;
        }
        .document-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg border-0 p-4">
        <h3 class="text-center">
            <?php echo $section_id ? htmlspecialchars($section['name']) : 'All Documents'; ?>
        </h3>
        <p class="text-center text-muted">
            Browse and download important documents from this section.
        </p>
        
        <div class="card-body">
            <!-- Search Bar -->
            <div class="d-flex justify-content-center">
                <div class="input-group search-bar">
                    <span class="input-group-text" id="search-icon"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search documents..."
                           aria-label="Search documents" aria-describedby="search-icon"
                           <?php echo empty($documents) ? 'disabled' : ''; ?>>
                </div>
            </div>

            <!-- No Results Message -->
            <div id="noResults" class="alert alert-warning text-center mt-3 hidden">
                No matching documents found.
            </div>

            <?php if (empty($documents)): ?>
                <div class="alert alert-warning text-center mt-3">No documents found.</div>
            <?php else: ?>
                <div class="document-container" id="documentContainer">
                    <?php foreach ($documents as $doc): ?>
                        <div class="card document-card p-3" style="width: 18rem;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($doc['title']); ?></h5>
                                <p class="card-text text-muted">
                                    Uploaded on: <?php echo date("F j, Y", strtotime($doc['created_at'])); ?>
                                </p>
                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-success btn-sm" target="_blank">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Search Functionality
    document.getElementById('searchInput').addEventListener('input', function () {
        let filter = this.value.toLowerCase();
        let cards = document.querySelectorAll('.document-card');
        let noResults = document.getElementById('noResults');
        let matchFound = false;

        cards.forEach(card => {
            let title = card.querySelector('.card-title').textContent.toLowerCase();
            if (title.includes(filter)) {
                card.style.display = "block";
                matchFound = true;
            } else {
                card.style.display = "none";
            }
        });

        noResults.classList.toggle("hidden", matchFound);
    });
</script>

</body>
</html>
