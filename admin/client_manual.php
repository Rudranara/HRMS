<?php
include("header.php");

$manualSections = [
    [
        'title' => 'Dashboard',
        'image' => '../uploads/manual/dashboard.png',
        'description' => 'The dashboard is the main overview page for admin users. It gives a quick picture of attendance, employees, salary activity, leave status, and other important business summaries.',
        'steps' => [
            'Open Dashboard from the left menu after login.',
            'Review the summary cards, charts, and report widgets to understand the current organization status.',
            'If the page shows Office, Month, Year, or other filters, select the values you want and click the filter button to refresh the dashboard data.',
            'Use this page for quick monitoring and decision support. It is mainly for review, not for detailed data entry.',
            'When you want to take action, open the related module directly from the sidebar.'
        ]
    ],
    [
        'title' => 'Today Attendance',
        'image' => '../uploads/manual/today_attendance.png',
        'description' => 'This page is used to monitor attendance activity for the current day. It helps admin review who has punched in, who is absent, and where correction may be required.',
        'steps' => [
            'Open Attendance -> Today Attendance.',
            'Review the employee list and check today’s punch in, punch out, attendance status, and timing information.',
            'Use search or filters if the page provides office, employee, or status-based filtering.',
            'If a record is incorrect, click the edit button to correct timing or attendance details according to your process.',
            'Use delete only when an entry was created by mistake and should not remain in the system.'
        ]
    ],
    [
        'title' => 'Attendance Record',
        'image' => '../uploads/manual/attendance_record.png',
        'description' => 'The attendance record page helps admin review historical attendance data for employees across dates, months, teams, or offices.',
        'steps' => [
            'Open Attendance -> Attendance Record.',
            'Choose the relevant filters such as office, employee, month, year, or date range.',
            'Click the filter or search button to load the report.',
            'Review punch in, punch out, attendance status, work hours, and any exception shown in the report.',
            'Use export, print, or report actions when you need attendance data for payroll or management review.'
        ]
    ],
    [
        'title' => 'Break Record',
        'image' => '../uploads/manual/breaks_report.png',
        'description' => 'This page is used to review employee break timing and break duration. It is helpful for audit, productivity review, and working-hour verification.',
        'steps' => [
            'Open Attendance -> Break Record.',
            'Apply employee, office, date, or month filters if you want a specific set of records.',
            'Click filter to load the break history.',
            'Review the start time, end time, and total break duration for each entry.',
            'Use this page whenever you need to validate attendance disputes or confirm working-hour calculations.'
        ]
    ],
    [
        'title' => 'Attendance Change Requests',
        'image' => '../uploads/manual/attendance_request.png',
        'description' => 'This page is used to review attendance correction requests submitted by employees. Admin decides whether a request should be approved or rejected.',
        'steps' => [
            'Open Attendance -> Change Requests.',
            'Review the employee name, requested date, old record, new request details, and the reason provided.',
            'Verify the request against attendance logs, manager confirmation, or company policy before taking a decision.',
            'Click approve if the correction is valid, or reject if the request is not acceptable.',
            'After approval, the corrected attendance should affect the employee’s attendance record and may later affect payroll processing.'
        ]
    ],
    [
        'title' => 'Visits',
        'image' => '../uploads/manual/visits.png',
        'description' => 'The visits page helps admin monitor field visits, client meetings, and travel-related activity logged by employees or teams.',
        'steps' => [
            'Open Visits from the sidebar.',
            'Review the visit list, client details, employee name, visit status, and date information.',
            'Use filters or search to narrow the visit list by employee, date, office, or status.',
            'Open a record or use any edit/update action if your process allows admin-side correction or follow-up updates.',
            'Use this page for monitoring field activity, reporting movement, and operational follow-up.'
        ]
    ],
    [
        'title' => 'Employee Management',
        'image' => '../uploads/manual/employees.png',
        'description' => 'This is the main employee master page. It is used to create new employee profiles, update existing records, and maintain core HR information.',
        'steps' => [
            'Open Employee from the left menu.',
            'To create a new employee, click the add employee button and fill in personal details, office details, salary data, attendance configuration, and login-related fields as required.',
            'To change an existing employee record, click edit on the relevant employee row and update the required information.',
            'Use delete, deactivate, or status controls carefully if the page provides them, because these actions may affect payroll, attendance, and access control.',
            'Keep employee records updated at all times so attendance, leave, salary, and reporting work correctly.'
        ]
    ],
    [
        'title' => 'Worksheet',
        'image' => '../uploads/manual/worksheet.png',
        'description' => 'The worksheet page is used to review structured work data, operational entries, or planning-related information in one place.',
        'steps' => [
            'Open Worksheet from the menu.',
            'Apply the available employee, office, or date filters if you want a specific report view.',
            'Click filter or search to load the worksheet data.',
            'Review tasks, work entries, summaries, or activity lines shown on the page according to your workflow configuration.',
            'Use this page for internal planning, review meetings, and work follow-up.'
        ]
    ],
    [
        'title' => 'Payroll Salary',
        'image' => '../uploads/manual/payroll.png',
        'description' => 'This page is used to generate monthly payroll, review salary components, and save final salary records for employees.',
        'steps' => [
            'Open Payroll -> Salary.',
            'Select office, employee or all employees, month, and year before clicking the filter button.',
            'Wait for the salary summary to load, then review each row carefully including CTC, deductions, leave effects, advance, tax, and final net salary.',
            'If manual fields are editable, update the required value carefully before saving.',
            'Click save salary summary or the final save button only after attendance and deductions are verified, because these records are used for official payroll.'
        ]
    ],
    [
        'title' => 'Payroll Summary',
        'image' => '../uploads/manual/salary_summary.png',
        'description' => 'The payroll summary page gives a broader salary overview and helps admin review generated salary data at a management level.',
        'steps' => [
            'Open Payroll -> Summary.',
            'Apply month, year, office, or other report filters shown on the page.',
            'Click filter to refresh the summary report.',
            'Review totals such as salary cost, deductions, net salary, and other summary values.',
            'Use this page for management reporting, payroll review, and comparison across different periods.'
        ]
    ],
    [
        'title' => 'Advance Requests',
        'image' => '../uploads/manual/advance_salary_request.png',
        'description' => 'This page is used to review employee advance salary requests. Admin can approve or reject monthly requests and can also allocate yearly advance requests month by month.',
        'steps' => [
            'Open Payroll -> Advance Requests.',
            'Review the employee name, request type, requested amount, reason, and current status shown in the request list.',
            'Use approve or reject after reviewing company policy and the validity of the request.',
            'If the request is yearly and already approved, click Allocate Month to assign part of that approved amount into a specific payroll month.',
            'After allocation, the approved amount can appear in payroll processing for that selected month.'
        ]
    ],
    [
        'title' => 'Expenses',
        'image' => '../uploads/manual/expenses.png',
        'description' => 'Use the expense management page to review employee expense claims and control reimbursement workflow.',
        'steps' => [
            'Open Expenses from the admin menu.',
            'Review the submitted claims, employee name, amount, category, purpose, and any attached proof or note.',
            'Use approve, reject, verify, or edit actions according to your internal approval process.',
            'If documents or claim values are incomplete, review them carefully before making a final decision.',
            'Approved records can be used for reimbursement and reporting, so maintain this page accurately.'
        ]
    ],
    [
        'title' => 'Site',
        'image' => '../uploads/manual/sites.png',
        'description' => 'The site page manages office, branch, or site-level setup that is later used in employee mapping, attendance, reporting, and payroll filters.',
        'steps' => [
            'Open Site from the left menu.',
            'Click add site if you want to create a new office or branch record.',
            'Enter site name, location, branch details, or other required configuration values.',
            'Use edit when site information changes, such as office address or naming updates.',
            'Keep site records accurate because they affect office filtering, employee mapping, and many reports.'
        ]
    ],
    [
        'title' => 'Tasks',
        'image' => '../uploads/manual/assign_task.png',
        'description' => 'This section is used to create, assign, and monitor general task activity within the system.',
        'steps' => [
            'Open Tasks -> Task.',
            'Create a new task if the page supports task assignment, then enter task title, employee, due date, instructions, and priority as needed.',
            'Review existing task records and use edit or status actions to update progress.',
            'Mark tasks correctly as pending, in progress, or completed according to actual work status.',
            'Use this page to maintain accountability and track operational work clearly.'
        ]
    ],
    [
        'title' => 'Daily Task',
        'image' => '../uploads/manual/daily_report.png',
        'description' => 'This page is used to monitor daily task or daily report entries submitted by employees.',
        'steps' => [
            'Open Tasks -> Daily Task.',
            'Review employee daily updates, work summaries, remarks, and completion details shown in the table or report.',
            'Use filters such as employee, team, or date if the page supports them.',
            'Click filter or search to refresh the results.',
            'Use this page to monitor day-wise progress, identify delays, and improve reporting quality.'
        ]
    ],
    [
        'title' => 'Track Employees',
        'image' => '../uploads/manual/track_employee.png',
        'description' => 'This page is used to monitor employee movement, live location, or travel-related activity where tracking is enabled.',
        'steps' => [
            'Open Track Employees from the menu.',
            'Use the map, listing, or employee filters shown on the page to review movement records.',
            'Check live or recent location activity of employees who are working in the field.',
            'Open detailed records when the page provides history or journey-level drill-down.',
            'Use this page responsibly for supervision, field planning, and movement review.'
        ]
    ],
    [
        'title' => 'Leave Requests',
        'image' => '../uploads/manual/leave_request.png',
        'description' => 'This page helps admin review employee leave requests and make approval decisions based on policy and leave balance.',
        'steps' => [
            'Open Leave -> Leave Requests.',
            'Review the employee name, leave dates, leave type, reason, and current request status.',
            'Check leave balance, policy rules, and team planning before making a decision.',
            'Click approve or reject based on the validity of the request.',
            'Approved leave should later affect attendance and may also affect payroll calculations depending on company policy.'
        ]
    ],
    [
        'title' => 'Leave Types',
        'image' => '../uploads/manual/leave_types.png',
        'description' => 'Use this page to maintain all leave categories available in the HRMS system.',
        'steps' => [
            'Open Leave -> Leave Types.',
            'Click add leave type if you want to create a new category such as casual leave, sick leave, or earned leave.',
            'Enter the leave type name and any related rules or limits if the form supports them.',
            'Use edit to update existing leave categories when policy changes.',
            'Maintain these values carefully because employees depend on them while applying for leave.'
        ]
    ],
    [
        'title' => 'Calendar',
        'image' => '../uploads/manual/calender.png',
        'description' => 'The calendar page is used to manage holidays, events, reminders, and date-based planning across the organization.',
        'steps' => [
            'Open Calendar from the menu.',
            'Review the calendar view to check upcoming holidays, events, or planning entries.',
            'Click add event or the date block if the page allows creating a new holiday or event record.',
            'Enter the title, date, and relevant notes before saving.',
            'Keep calendar entries updated because these dates support attendance planning and leave awareness.'
        ]
    ],
    [
        'title' => 'Users',
        'image' => '../uploads/manual/admin_user.png',
        'description' => 'This page is used to manage system login users and control who can access different parts of the HRMS.',
        'steps' => [
            'Open Users from the menu.',
            'Click add user if you want to create a new login account.',
            'Enter the required details such as name, username, email, password, role, and access permissions if the form supports them.',
            'Use edit when a user role or access permission needs to change.',
            'Disable or remove unused accounts to keep the system secure and controlled.'
        ]
    ],
    [
        'title' => 'Organization',
        'image' => '../uploads/manual/organization.png',
        'description' => 'This page stores company-level settings such as organization details, branding, and other core information used across the HRMS.',
        'steps' => [
            'Open Organization from the menu.',
            'Review the existing organization profile including company name, contact details, logo, address, and any other configured values.',
            'Click edit or update to change information when company details change.',
            'Save the changes after confirming the values are correct.',
            'Organization settings often appear in reports, salary slips, and system branding, so keep them accurate.'
        ]
    ],
    [
        'title' => 'Tickets',
        'image' => '../uploads/manual/tickets.png',
        'description' => 'The ticket section is used to manage internal support issues, service requests, and employee problem reports.',
        'steps' => [
            'Open Tickets from the menu.',
            'Review each ticket to check subject, issue description, employee, priority, and current status.',
            'Assign the ticket to the correct person or support team if assignment controls are available.',
            'Update status such as open, in progress, resolved, or closed as work continues.',
            'Use this page to maintain support accountability and keep issue resolution visible to the organization.'
        ]
    ],
    [
        'title' => 'Leads',
        'image' => '../uploads/manual/lead_management.png',
        'description' => 'The leads section is used to manage the full sales or enquiry pipeline, including lead overview, follow-up work, and lead monitoring.',
        'steps' => [
            'Open Leads and then choose the required sub-page such as Lead Dashboard, Manage Leads, Bulk Lead Upload, or Follow-up Monitor.',
            'Use Lead Dashboard to review overall lead performance and stage summaries.',
            'Use Manage Leads to add, edit, assign, and update lead records after each client interaction.',
            'Use Bulk Lead Upload when you need to import many lead records together from an external sheet or file.',
            'Use Follow-up Monitor to check which leads need action so no important follow-up is missed.'
        ]
    ],
    [
        'title' => 'Assets',
        'image' => '../uploads/manual/assets_management.png',
        'description' => 'The assets section is used to manage company-issued assets, their verification records, and recovery or return workflow.',
        'steps' => [
            'Open Assets and choose the required sub-page such as Manage Assets, Asset Verification, or Asset Recovery.',
            'Use Manage Assets to create asset records, assign assets to employees, and update asset details.',
            'Use Asset Verification to confirm asset issue, related documents, or acknowledgment details where applicable.',
            'Use Asset Recovery when an employee returns an asset or when recovery and deduction tracking is required.',
            'Maintain this section carefully because assets affect inventory control, accountability, and employee clearance workflow.'
        ]
    ]
];
?>

<style>
  .manual-page {
    --manual-ink: #182230;
    --manual-muted: #657285;
    --manual-accent: #15314f;
    --manual-accent-soft: #e8eef6;
    --manual-border: rgba(108, 122, 138, 0.18);
    --manual-surface: rgba(255, 255, 255, 0.9);
    --manual-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
    background:
      radial-gradient(circle at top left, rgba(21, 49, 79, 0.08), transparent 26%),
      radial-gradient(circle at top right, rgba(148, 163, 184, 0.14), transparent 30%),
      linear-gradient(180deg, #f6f7f8 0%, #f1f4f6 100%);
  }

  .manual-page .manual-hero,
  .manual-page .manual-section,
  .manual-page .manual-quick-nav {
    border: 1px solid var(--manual-border);
    border-radius: 26px;
    box-shadow: var(--manual-shadow);
    background: var(--manual-surface);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }

  .manual-page .manual-hero {
    position: relative;
    overflow: hidden;
    padding: 1.45rem 1.5rem;
    background:
      linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(246, 249, 252, 0.96) 100%);
  }

  .manual-page .manual-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
      linear-gradient(120deg, rgba(21, 49, 79, 0.06), transparent 34%),
      radial-gradient(circle at 88% 18%, rgba(21, 49, 79, 0.12), transparent 18%);
    pointer-events: none;
  }

  .manual-page .manual-hero::after {
    content: "";
    position: absolute;
    right: -70px;
    bottom: -90px;
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(148, 163, 184, 0.22), transparent 68%);
    pointer-events: none;
  }

  .manual-page .manual-title,
  .manual-page .manual-intro {
    position: relative;
    z-index: 1;
  }

  .manual-page .manual-title {
    margin: 0 0 0.45rem;
    color: var(--manual-ink);
    font-size: clamp(1.75rem, 1.3rem + 1vw, 2.45rem);
    font-weight: 800;
    letter-spacing: -0.03em;
  }

  .manual-page .manual-intro {
    max-width: 780px;
    margin: 0;
    color: var(--manual-muted);
    font-size: 0.97rem;
    line-height: 1.75;
  }

  .manual-page .manual-sticky-nav {
    position: sticky;
    top: 0.7rem;
    z-index: 30;
    margin: 0 0 1.35rem;
  }

  .manual-page .manual-quick-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    padding: 0.85rem;
  }

  .manual-page .manual-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.5rem 0.95rem;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid rgba(108, 122, 138, 0.16);
    color: #334155;
    font-size: 0.83rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, color 0.18s ease, background 0.18s ease;
  }

  .manual-page .manual-chip:hover {
    transform: translateY(-2px);
    color: var(--manual-accent);
    border-color: rgba(21, 49, 79, 0.18);
    background: #f8fafc;
    box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
  }

  .manual-page .manual-chip:focus-visible {
    outline: 2px solid rgba(21, 49, 79, 0.2);
    outline-offset: 3px;
  }

  .manual-page .manual-section {
    position: relative;
    padding: 1.65rem;
    overflow: hidden;
    scroll-margin-top: 6rem;
    animation: manualReveal 0.35s ease both;
  }

  .manual-page .manual-section::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 1px;
    background: linear-gradient(90deg, rgba(21, 49, 79, 0.18), transparent 60%);
  }

  .manual-page .manual-section + .manual-section {
    margin-top: 1.55rem;
  }

  .manual-page .manual-alt {
    background: linear-gradient(180deg, rgba(250, 251, 252, 0.96) 0%, rgba(255, 255, 255, 0.96) 100%);
  }

  .manual-page .manual-copy {
    max-width: 540px;
  }

  .manual-page .manual-section-title {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1rem;
    color: var(--manual-ink);
  }

  .manual-page .manual-section-title::before {
    content: "";
    width: 10px;
    height: 38px;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--manual-accent), #8fa5bb);
    box-shadow: 0 10px 24px rgba(21, 49, 79, 0.18);
    flex-shrink: 0;
  }

  .manual-page .manual-section-title h4 {
    margin: 0;
    font-size: 1.85rem;
    line-height: 1.08;
    letter-spacing: -0.03em;
  }

  .manual-page .manual-section-text {
    margin-bottom: 1.15rem;
    color: var(--manual-muted);
    font-size: 0.96rem;
    line-height: 1.8;
  }

  .manual-page .manual-steps {
    margin: 0;
    padding: 0;
    list-style: none;
    counter-reset: manual-step;
  }

  .manual-page .manual-steps li {
    position: relative;
    margin-bottom: 0.8rem;
    padding: 0.9rem 1rem 0.9rem 4rem;
    border: 1px solid rgba(108, 122, 138, 0.14);
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #334155;
    line-height: 1.7;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
    counter-increment: manual-step;
  }

  .manual-page .manual-steps li:last-child {
    margin-bottom: 0;
  }

  .manual-page .manual-steps li::before {
    content: counter(manual-step);
    position: absolute;
    left: 1rem;
    top: 0.82rem;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--manual-accent) 0%, #2b4d72 100%);
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 800;
    box-shadow: 0 12px 24px rgba(21, 49, 79, 0.18);
  }

  .manual-page .manual-preview {
    position: relative;
    height: 100%;
    min-height: 430px;
    border-radius: 24px;
    border: 1px solid rgba(108, 122, 138, 0.18);
    background: linear-gradient(180deg, #f7f9fb 0%, #eef2f5 100%);
    box-shadow: 0 24px 45px rgba(15, 23, 42, 0.09);
    overflow: hidden;
  }

  .manual-page .manual-preview::after {
    content: "";
    position: absolute;
    inset: auto -60px -80px auto;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(21, 49, 79, 0.1), transparent 70%);
    pointer-events: none;
  }

  .manual-page .manual-preview-bar {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 1rem;
    background: rgba(255, 255, 255, 0.92);
    border-bottom: 1px solid rgba(108, 122, 138, 0.14);
  }

  .manual-page .manual-preview-label {
    margin-left: auto;
    color: #64748b;
    font-size: 0.77rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .manual-page .manual-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.45);
  }

  .manual-page .manual-dot.red { background: #ef8e8e; }
  .manual-page .manual-dot.yellow { background: #f2cf7a; }
  .manual-page .manual-dot.green { background: #93d4bc; }

  .manual-page .manual-preview-body {
    position: relative;
    min-height: 372px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    text-align: center;
    color: #718096;
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.66), rgba(255, 255, 255, 0.2)),
      repeating-linear-gradient(
        135deg,
        rgba(21, 49, 79, 0.04),
        rgba(21, 49, 79, 0.04) 14px,
        rgba(255, 255, 255, 0.16) 14px,
        rgba(255, 255, 255, 0.16) 28px
      );
  }

  .manual-page .manual-preview-body span {
    max-width: 280px;
    display: inline-block;
  }

  .manual-page .manual-preview-link {
    display: block;
    width: 100%;
    text-decoration: none;
    color: inherit;
  }

  .manual-page .manual-preview-image {
    width: 100%;
    height: 100%;
    min-height: 372px;
    display: block;
    object-fit: contain;
    object-position: top center;
    background: #f8fafc;
    border-radius: 16px;
    transition: transform 0.28s ease, box-shadow 0.28s ease;
  }

  .manual-page .manual-preview:hover .manual-preview-image {
    transform: scale(1.025);
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
  }

  .manual-page .manual-hint {
    margin-top: 0.9rem;
    color: #6b7280;
    font-size: 0.77rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  @keyframes manualReveal {
    from {
      opacity: 0;
      transform: translateY(8px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (min-width: 992px) {
    .manual-page .manual-section--reverse .manual-copy {
      margin-left: auto;
    }
  }

  @media (max-width: 991.98px) {
    .manual-page .manual-section {
      padding: 1.35rem;
    }

    .manual-page .manual-copy {
      max-width: none;
    }
  }

  @media (max-width: 767.98px) {
    .manual-page .manual-hero,
    .manual-page .manual-section,
    .manual-page .manual-quick-nav {
      border-radius: 20px;
    }

    .manual-page .manual-hero,
    .manual-page .manual-section {
      padding: 1rem;
    }

    .manual-page .manual-title {
      font-size: 1.7rem;
    }

    .manual-page .manual-section-title h4 {
      font-size: 1.45rem;
    }

    .manual-page .manual-steps li {
      padding: 0.85rem 0.9rem 0.85rem 3.65rem;
    }

    .manual-page .manual-preview {
      min-height: 290px;
    }

    .manual-page .manual-preview-body,
    .manual-page .manual-preview-image {
      min-height: 245px;
    }

    .manual-page .manual-quick-nav {
      flex-wrap: nowrap;
      overflow-x: auto;
      padding: 0.65rem;
    }

    .manual-page .manual-sticky-nav {
      top: 0.2rem;
    }
  }
</style>

<div class="container-fluid container-fluid-main manual-page py-4">
  <div class="manual-hero mb-4">
    
    <h2 class="manual-title">Admin HRMS System User Manual</h2>
    <p class="manual-intro">This page helps clients understand the main modules of the HRMS system step by step.</p>
  </div>

  <div class="manual-sticky-nav">
    <div class="manual-quick-nav">
      <?php foreach ($manualSections as $index => $section): ?>
        <a href="#manual-section-<?= $index + 1 ?>" class="manual-chip"><?= htmlspecialchars($section['title']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php foreach ($manualSections as $index => $section): ?>
    <?php $reverse = $index % 2 === 1; ?>
    <div class="manual-section <?= $reverse ? 'manual-alt manual-section--reverse' : '' ?>" id="manual-section-<?= $index + 1 ?>">
      <div class="row align-items-center g-4">
        <?php if ($reverse): ?>
          <div class="col-lg-7 order-2 order-lg-1">
            <div class="manual-preview">
              <div class="manual-preview-bar">
                <span class="manual-dot red"></span>
                <span class="manual-dot yellow"></span>
                <span class="manual-dot green"></span>
                <span class="manual-preview-label">Screenshot Preview</span>
              </div>
              <div class="manual-preview-body">
                <?php if (!empty($section['image'])): ?>
                  <a href="<?= htmlspecialchars($section['image']) ?>" target="_blank" class="manual-preview-link">
                    <img src="<?= htmlspecialchars($section['image']) ?>" alt="<?= htmlspecialchars($section['title']) ?> Screenshot" class="manual-preview-image">
                  </a>
                <?php else: ?>
                  <span>Insert <?= htmlspecialchars($section['title']) ?> screenshot here</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="manual-hint">Click the screenshot to open the full image.</div>
          </div>
          <div class="col-lg-5 order-1 order-lg-2">
            <div class="manual-copy">
            <div class="manual-section-title">
              <h4><?= htmlspecialchars($section['title']) ?></h4>
            </div>
            <p class="manual-section-text"><?= htmlspecialchars($section['description']) ?></p>
            <ul class="manual-steps">
              <?php foreach ($section['steps'] as $step): ?>
                <li><?= htmlspecialchars($step) ?></li>
              <?php endforeach; ?>
            </ul>
            </div>
          </div>
        <?php else: ?>
          <div class="col-lg-5">
            <div class="manual-copy">
            <div class="manual-section-title">
              <h4><?= htmlspecialchars($section['title']) ?></h4>
            </div>
            <p class="manual-section-text"><?= htmlspecialchars($section['description']) ?></p>
            <ul class="manual-steps">
              <?php foreach ($section['steps'] as $step): ?>
                <li><?= htmlspecialchars($step) ?></li>
              <?php endforeach; ?>
            </ul>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="manual-preview">
              <div class="manual-preview-bar">
                <span class="manual-dot red"></span>
                <span class="manual-dot yellow"></span>
                <span class="manual-dot green"></span>
                <span class="manual-preview-label">Screenshot Preview</span>
              </div>
              <div class="manual-preview-body">
                <?php if (!empty($section['image'])): ?>
                  <a href="<?= htmlspecialchars($section['image']) ?>" target="_blank" class="manual-preview-link">
                    <img src="<?= htmlspecialchars($section['image']) ?>" alt="<?= htmlspecialchars($section['title']) ?> Screenshot" class="manual-preview-image">
                  </a>
                <?php else: ?>
                  <span>Insert <?= htmlspecialchars($section['title']) ?> screenshot here</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="manual-hint">Click the screenshot to open the full image.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include("footer.php"); ?>
