<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>Login required.</div>";
    exit;
}

require 'db_connection.php';

$employee_id = $_SESSION['employee_id'];

// Check if current user is a Manager
$stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'Manager') {
    echo "<div class='alert alert-danger'>Access denied. Only managers can view hierarchy.</div>";
    exit;
}

// Recursive function to fetch employees under a manager
function getEmployeeHierarchy($conn, $manager_id)
{
    $stmt = $conn->prepare("SELECT id, name, role, photo FROM employees WHERE manager = ?");
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $row['subordinates'] = getEmployeeHierarchy($conn, $row['id']); // Recursive call
        $employees[] = $row;
    }
    $stmt->close();
    return $employees;
}

// Fetch hierarchy for current manager
$hierarchy = getEmployeeHierarchy($conn, $employee_id);

// Function to render tree as nested HTML lists
function renderHierarchy($employees)
{
    if (empty($employees)) return;

    echo "<ul class='tree'>";
    foreach ($employees as $emp) {
        $photo = !empty($emp['photo']) ? "{$emp['photo']}" : "assets/img/logos/user.png";

        $hasSubordinates = !empty($emp['subordinates']);
        $toggleIcon = $hasSubordinates ? "<span class='toggle-btn' onclick='toggleNode(this)'>▶</span>" : "";

        echo "<li>";
        echo "<div class='node d-flex align-items-center'>";
        echo $toggleIcon;
        echo "<img src='$photo' alt='{$emp['name']}' class='profile-img me-2'>";
        echo "<div><strong>{$emp['name']}</strong><br><small>{$emp['role']}</small></div>";
        echo "</div>";

        if ($hasSubordinates) {
            echo "<div class='sub-tree'>";
            renderHierarchy($emp['subordinates']);
            echo "</div>";
        }

        echo "</li>";
    }
    echo "</ul>";
}



?>
<style>
.hierarchy-page {
    padding-top: 0.9rem !important;
    padding-bottom: 1.2rem !important;
}

.hierarchy-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 28px;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.hierarchy-card .card-header {
    padding: 1.2rem 1.35rem 0;
    border: 0;
    background: transparent;
}

.hierarchy-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.12rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.hierarchy-card .card-body {
    padding: 1.15rem 1.35rem 1.35rem;
    background: transparent;
}

.hierarchy-tree-wrap {
    padding: 0.2rem;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.72);
}

.tree {
    list-style: none;
    margin: 0;
    padding-left: 0.45rem;
    position: relative;
}

.tree li {
    position: relative;
    margin-bottom: 0.85rem;
    padding-left: 1.75rem;
}

.tree li:last-child {
    margin-bottom: 0;
}

.tree li::before {
    content: "";
    position: absolute;
    top: 1.35rem;
    left: 0.25rem;
    width: 1rem;
    height: 1px;
    background: linear-gradient(90deg, #cbd5e1 0%, #94a3b8 100%);
}

.tree li::after {
    content: "";
    position: absolute;
    top: -0.9rem;
    left: 0.25rem;
    width: 1px;
    height: calc(100% + 0.9rem);
    background: linear-gradient(180deg, #dbe4ef 0%, #b9c7d8 100%);
}

.tree > li::after {
    top: 1.35rem;
    height: calc(100% - 0.5rem);
}

.tree li:last-child::after {
    height: 2.25rem;
}

.node {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-height: 72px;
    max-width: 430px;
    margin-top: 0.2rem;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(203, 213, 225, 0.9);
    border-radius: 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.node:hover {
    transform: translateY(-1px);
    border-color: rgba(59, 130, 246, 0.28);
    box-shadow: 0 22px 40px rgba(15, 23, 42, 0.12);
}

.node strong {
    display: block;
    color: #0f172a;
    font-size: 0.96rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.node small {
    display: inline-flex;
    align-items: center;
    margin-top: 0.18rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #edf4ff;
    color: #295c9b;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.profile-img {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16);
    flex-shrink: 0;
}

.toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    margin-right: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, #173f7a 0%, #214f93 100%);
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 800;
    line-height: 1;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 10px 18px rgba(23, 63, 122, 0.2);
    flex-shrink: 0;
}

.sub-tree {
    margin-left: 0.65rem;
    padding-top: 0.55rem;
    display: block;
}

@media (max-width: 767.98px) {
    .hierarchy-page {
        padding-top: 0.65rem !important;
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        padding-bottom: 0.85rem !important;
    }

    .hierarchy-card {
        border-radius: 24px;
    }

    .hierarchy-card .card-header {
        padding: 1rem 1rem 0;
    }

    .hierarchy-card .card-body {
        padding: 0.95rem 1rem 1rem;
    }

    .hierarchy-title {
        font-size: 0.98rem;
        line-height: 1.24;
    }

    .hierarchy-tree-wrap {
        padding: 0;
        background: transparent;
    }

    .tree {
        padding-left: 0.1rem;
    }

    .tree li {
        padding-left: 1.3rem;
        margin-bottom: 0.72rem;
    }

    .tree li::before {
        top: 1.2rem;
        width: 0.78rem;
    }

    .tree li::after,
    .tree > li::after {
        left: 0.18rem;
    }

    .node {
        gap: 0.7rem;
        min-height: 64px;
        max-width: none;
        width: 100%;
        padding: 0.78rem 0.82rem;
        border-radius: 18px;
    }

    .profile-img {
        width: 38px;
        height: 38px;
        border-radius: 12px;
    }

    .toggle-btn {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        font-size: 0.72rem;
    }

    .node strong {
        font-size: 0.9rem;
    }

    .node small {
        font-size: 0.65rem;
        letter-spacing: 0.04em;
    }

    .sub-tree {
        margin-left: 0.3rem;
        padding-top: 0.45rem;
    }
}
</style>


<div class="container-fluid py-4 hierarchy-page">
    <div class="card hierarchy-card">
        <div class="card-header">
            <h5 class="hierarchy-title">My Team Hierarchy</h5>
        </div>
        <div class="card-body">
            <div class="hierarchy-tree-wrap">
                <?php renderHierarchy($hierarchy); ?>
            </div>
        </div>
    </div>
</div>

    <script>
function toggleNode(element) {
    const subTree = element.parentElement.parentElement.querySelector(".sub-tree");
    if (subTree) {
        if (subTree.style.display === "none") {
            subTree.style.display = "block";
            element.innerText = "▶";
        } else {
            subTree.style.display = "none";
            element.innerText = "▼";
        }
    }
}
</script>


<?php include("footer.php"); ?>
