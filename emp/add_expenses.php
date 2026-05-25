
<?php
include("header.php");
date_default_timezone_set('Asia/Kolkata');

$employee_id = $_SESSION['employee_id']; 
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_type = $_POST['expense_type'];
    $expense_date = $_POST['expense_date'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $status = "Pending"; // Default status is Pending
    $upload_dir = "../uploads/expenses/";
    $upload_dir_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'expenses' . DIRECTORY_SEPARATOR;
    $quantity = $_POST['quantity'] ?? null;
    $unit = $_POST['unit'] ?? null;

    // Insert expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (employee_id, expense_type, expense_date, title, description, amount, quantity, unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisds", $employee_id, $expense_type, $expense_date, $title, $description, $amount, $quantity, $unit, $status);

    if ($stmt->execute()) {
        $expense_id = $stmt->insert_id;

        // Handle multiple file uploads
        if (!empty($_FILES['documents']['name'][0])) {
            if (!is_dir($upload_dir_path) && !mkdir($upload_dir_path, 0777, true) && !is_dir($upload_dir_path)) {
                $message = "Expense added, but the upload folder could not be created.";
                $message_type = "error";
            }

            foreach ($_FILES['documents']['name'] as $key => $filename) {
                $safe_filename = basename($filename);
                $target_file = $upload_dir . $safe_filename;
                $target_file_path = $upload_dir_path . $safe_filename;

                if (is_dir($upload_dir_path) && move_uploaded_file($_FILES['documents']['tmp_name'][$key], $target_file_path)) {
                    // Insert document record into the database
                    $stmt = $conn->prepare("INSERT INTO expense_documents (expense_id, file_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $expense_id, $target_file);
                    $stmt->execute();
                }
            }
        }
        if ($message === "") {
            $message = "Expense added successfully!";
            $message_type = "success";
        }
    } else {
        $message = "Failed to add expense!";
        $message_type = "error";
    }
}
?>

<style>
    body {
        background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
    }

    .add-expense-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

        .add-expense-shell {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 30px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
            width: 100%;
    }

    .add-expense-inner {
        padding: 1.3rem !important;
    }

    .add-expense-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .add-expense-subtitle {
        margin-top: 0.3rem;
        color: #64748b;
        font-size: 0.86rem;
        font-weight: 500;
    }

    .add-expense-alert {
        margin-top: 1rem;
        margin-bottom: 0;
        border-radius: 16px;
        font-weight: 600;
    }

    .add-expense-alert-success {
        border: 1px solid #86efac;
        background: #dcfce7;
        color: #166534;
    }

    .add-expense-alert-error {
        border: 1px solid #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .add-expense-form {
        margin-top: 1rem;
    }

    .add-expense-form .row {
        --bs-gutter-x: 1rem;
        --bs-gutter-y: 0;
    }

    .add-expense-group {
        margin-top: 1rem !important;
    }

    .add-expense-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .add-expense-field {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid #d9e2ec;
        background: #ffffff;
        color: #0f172a;
        box-shadow: none;
        font-size: 0.92rem;
        font-weight: 500;
        padding: 0.8rem 0.95rem;
    }

    .add-expense-field:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    textarea.add-expense-field {
        min-height: 96px;
        resize: vertical;
    }

    .add-expense-upload {
        margin-top: 1rem !important;
        padding: 1rem;
        border-radius: 22px;
        background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
        border: 1px solid #dbe4f0;
    }

    .add-expense-upload .input-group {
        align-items: stretch;
    }

    .add-expense-upload .form-control {
        min-height: 48px;
        border-radius: 16px 0 0 16px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .add-expense-upload .btn {
        min-height: 48px;
        border-radius: 0 16px 16px 0;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .add-file {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        border: 0 !important;
        color: #ffffff !important;
    }

    .remove-file {
        background: linear-gradient(135deg, #ffe3e3 0%, #fecaca 100%) !important;
        border: 1px solid #fca5a5 !important;
        color: #b91c1c !important;
    }

    .add-expense-submit-wrap {
        margin-top: 1.1rem !important;
    }

    .add-expense-submit {
        min-height: 48px;
        padding: 0.8rem 1rem;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    @media (max-width: 767.98px) {
        .add-expense-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .add-expense-shell {
            border-radius: 22px;
        }

        .add-expense-inner {
            padding: 1rem !important;
        }

        .add-expense-title {
            font-size: 1rem;
        }

        .add-expense-subtitle {
            font-size: 0.78rem;
        }

        .add-expense-form .row {
            --bs-gutter-x: 0.75rem;
        }

        .add-expense-field,
        .add-expense-upload .form-control,
        .add-expense-upload .btn,
        .add-expense-submit {
            min-height: 44px;
            border-radius: 14px;
            font-size: 0.78rem;
        }

        .add-expense-upload {
            padding: 0.85rem;
            border-radius: 18px;
        }

        .add-expense-upload .form-control {
            border-radius: 14px 0 0 14px;
        }

        .add-expense-upload .btn {
            border-radius: 0 14px 14px 0;
            font-size: 0.68rem;
        }
    }
</style>

<div class="container-fluid py-4 add-expense-page">
    <div class="add-expense-shell">
        <div class="add-expense-inner">
    <h3 class="add-expense-title">Add Daily Expense</h3>
    <div class="add-expense-subtitle">Submit an expense entry with the same fields and upload flow in a cleaner, more professional layout.</div>
    <?php if (!empty($message)) : ?>
        <div class="alert add-expense-alert add-expense-alert-<?= htmlspecialchars($message_type ?: 'success') ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form id="expenseForm" class="add-expense-form" action="add_expenses" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-6 mt-4 add-expense-group">
                <label for="expense_type" class="form-label add-expense-label">Expense Type</label>
                <select name="expense_type" id="expense_type" class="form-control add-expense-field" required>
                    <option value="" disabled selected>Select Expense Type</option>
                    <option value="Goods">Goods</option>
                    <option value="Services">Services</option>
                </select>
            </div>
            <div class="col-md-6 mt-4 add-expense-group">
                <label for="expense_date" class="form-label add-expense-label">Expense Date</label>
                <input type="date" name="expense_date" id="expense_date" class="form-control add-expense-field" required>
            </div>

            <div class="col-md-6 mt-4 add-expense-group">
                <label for="title" class="form-label add-expense-label">Expense Title</label>
                <input type="text" name="title" id="title" class="form-control add-expense-field" required>
            </div>
            <div class="col-md-6 mt-4 add-expense-group">
                <label for="description" class="form-label add-expense-label">Expense Description</label>
                <textarea name="description" id="description" class="form-control add-expense-field" rows="2" required></textarea>
            </div>

            <!-- Additional Fields for Goods -->
            <div class="col-md-6 mt-4 add-expense-group" id="quantity_field" style="display: none;">
                <label for="quantity" class="form-label add-expense-label">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control add-expense-field">
            </div>
            <div class="col-md-6 mt-4 add-expense-group" id="unit_field" style="display: none;">
                <label for="unit" class="form-label add-expense-label">Unit</label>
                <input type="text" name="unit" id="unit" class="form-control add-expense-field">
            </div>

            <div class="col-md-6 mt-4 add-expense-group">
                <label for="amount" class="form-label add-expense-label">Expense Amount</label>
                <input type="number" name="amount" id="amount" class="form-control add-expense-field" required>
            </div>

            <div class="col-md-7 mt-4 add-expense-group">
                <div class="add-expense-upload">
                <label for="documents" class="form-label add-expense-label">Attach Proof Document</label>
                <div id="file-input-container">
                    <div class="input-group mb-3">
                        <input type="file" name="documents[]" id="documents" class="form-control" accept=".pdf, .doc, .docx, .jpg, .png">
                        <button type="button" class="btn btn-secondary add-file">Add More</button>
                    </div>
                </div>
                </div>
            </div>

            <div class="col-md-6 mt-4 add-expense-submit-wrap">
                <button type="submit" class="btn btn-primary add-expense-submit">Add Expense</button>
            </div>
        </div>
    </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const expenseType = document.getElementById("expense_type");
    const amount = document.getElementById("amount");
    const quantityField = document.getElementById("quantity_field");
    const unitField = document.getElementById("unit_field");
    const documents = document.getElementById("documents");
    const fileContainer = document.getElementById("file-input-container");
    const expenseDate = document.getElementById("expense_date");

    // Set max selectable date (last 15 days)
    let today = new Date();
    let minDate = new Date();
    minDate.setDate(today.getDate() - 15);
    expenseDate.setAttribute("max", today.toISOString().split("T")[0]);
    expenseDate.setAttribute("min", minDate.toISOString().split("T")[0]);

    // Show/hide fields based on expense type
    expenseType.addEventListener("change", function () {
        if (this.value === "Goods") {
            quantityField.style.display = "block";
            unitField.style.display = "block";
        } else {
            quantityField.style.display = "none";
            unitField.style.display = "none";
        }
    });

    // Restrict max amount and set proof document requirement
    amount.addEventListener("input", function () {
        let value = parseFloat(this.value);
        if (expenseType.value === "Goods" && value > 10000) {
            alert("You cannot add more than ₹10,000 for Goods.");
            this.value = 10000;
        } else if (expenseType.value === "Services" && value > 5000) {
            alert("You cannot add more than ₹5,000 for Services.");
            this.value = 5000;
        }

        // Make proof document required
        if ((expenseType.value === "Goods" && value > 300) || expenseType.value === "Services") {
            documents.setAttribute("required", "true");
        } else {
            documents.removeAttribute("required");
        }
    });

    // Add/remove additional file input fields
    fileContainer.addEventListener("click", function (e) {
        if (e.target.classList.contains("add-file")) {
            const newInput = document.createElement("div");
            newInput.classList.add("input-group", "mb-3");
            newInput.innerHTML = `
                <input type="file" name="documents[]" class="form-control" accept=".pdf, .doc, .docx, .jpg, .png">
                <button type="button" class="btn btn-danger remove-file">Remove</button>
            `;
            fileContainer.appendChild(newInput);
        } else if (e.target.classList.contains("remove-file")) {
            e.target.parentElement.remove();
        }
    });
});
</script>

<?php include("footer.php"); ?>
