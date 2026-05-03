<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = $conn->real_escape_string($_POST['template_name']);
    $version = $conn->real_escape_string($_POST['version'] ?? '1.0');
    
    $conn->begin_transaction();
    try {
        // 1. Create Template
        $stmt = $conn->prepare("INSERT INTO questionnaire_template (template_name, version_no, status) VALUES (?, ?, 'Active')");
        $stmt->bind_param("ss", $template_name, $version);
        $stmt->execute();
        $template_id = $conn->insert_id;
        
        // 2. Add Questions
        $questions = $_POST['questions'] ?? [];
        $types = $_POST['types'] ?? [];
        
        $qStmt = $conn->prepare("INSERT INTO question (template_id, question_text, question_type, display_order) VALUES (?, ?, ?, ?)");
        
        for ($i = 0; $i < count($questions); $i++) {
            if (empty($questions[$i])) continue;
            $order = $i + 1;
            $qStmt->bind_param("issi", $template_id, $questions[$i], $types[$i], $order);
            $qStmt->execute();
        }
        
        $conn->commit();
        header("Location: manage_questionnaires.php?success=created");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating questionnaire: " . $e->getMessage();
    }
}

include_once 'includes/header.php';
?>

<div class="row fade-in-up">
    <div class="col-lg-8 mx-auto">
        <div class="mb-4">
            <a href="manage_questionnaires.php" class="text-decoration-none text-muted"><i class="fa-solid fa-arrow-left me-1"></i> Back to Management</a>
            <h2 class="fw-bold mt-2 text-primary">Create New Questionnaire</h2>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="templateForm">
            <div class="card glass-card mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Template Basics</h5>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Template Name</label>
                            <input type="text" name="template_name" class="form-control" placeholder="e.g., Weekly Post-Op Check" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Version</label>
                            <input type="text" name="version" class="form-control" value="1.0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card glass-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Questions</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuestionRow()">
                            <i class="fa-solid fa-plus me-1"></i> Add Question
                        </button>
                    </div>
                    
                    <div id="questionsContainer">
                        <div class="question-row row g-3 mb-3 pb-3 border-bottom">
                            <div class="col-md-8">
                                <label class="form-label small text-muted">Question Text</label>
                                <input type="text" name="questions[]" class="form-control" placeholder="Enter question..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Type</label>
                                <select name="types[]" class="form-select">
                                    <option value="YesNo">Yes / No</option>
                                    <option value="Numeric">Numeric (0-10)</option>
                                    <option value="Text">Open Text</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-premium px-5 py-2 shadow-sm">
                    <i class="fa-solid fa-save me-2"></i> Save Template
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addQuestionRow() {
    const container = document.getElementById('questionsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'question-row row g-3 mb-3 pb-3 border-bottom';
    newRow.innerHTML = `
        <div class="col-md-8">
            <input type="text" name="questions[]" class="form-control" placeholder="Enter question..." required>
        </div>
        <div class="col-md-3">
            <select name="types[]" class="form-select">
                <option value="YesNo">Yes / No</option>
                <option value="Numeric">Numeric (0-10)</option>
                <option value="Text">Open Text</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.question-row').remove()">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}
</script>

<?php include_once 'includes/footer.php'; ?>
