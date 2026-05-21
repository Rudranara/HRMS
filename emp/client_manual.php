<?php
include("header.php");

$manualSections = [
    [
        'title' => 'Dashboard',
        'image' => '../uploads/emp_manual/dashboard.png',
        'description' => 'The dashboard is the employee home page. Use it to quickly understand your current attendance status, pending work, leave updates, and other important highlights before moving to a detailed module.',
        'steps' => [
            'Open Dashboard from the left menu whenever you log in.',
            'Review the summary cards, charts, or activity blocks shown on the page to understand your current status.',
            'If filters are available, choose the required month, year, office, or other option and click the filter button to refresh the data.',
            'Use the dashboard for quick monitoring only. If you need to make a change or take action, open the related module from the menu.',
            'Come back to this page anytime to get a quick overview of your HRMS activity.'
        ]
    ],
    [
        'title' => 'Punch Attendance',
        'image' => '../uploads/emp_manual/punch_in.png',
        'description' => 'This page is used to mark your office attendance. It is the main page for recording your work start and work end time for the day.',
        'steps' => [
            'Open Attendance -> Punch from the sidebar.',
            'At the start of the day, click the punch-in button shown on the page. This records your login time for attendance.',
            'At the end of the day, open the same page and click the punch-out button to complete your attendance entry.',
            'Before clicking the button, make sure your internet connection, system time, and location permission are correct if the page uses GPS validation.',
            'After submission, the system stores your attendance and it becomes visible in My Attendance and attendance reports.'
        ]
    ],
    [
        'title' => 'Break',
        'image' => '../uploads/emp_manual/breaks.png',
        'description' => 'Use this page to record break timing during working hours. This helps the system maintain proper break history and accurate work-hour calculations.',
        'steps' => [
            'Open Attendance -> Break.',
            'When you leave for a break, click the start break button or break-in action shown on the page.',
            'When you return, click the end break button or break-out action to close the break entry.',
            'Always record break timing correctly, because the system may use this information in total working-hour calculations.',
            'Once submitted, the record appears in Break Records where you can review the full history later.'
        ]
    ],
    [
        'title' => 'My Attendance',
        'image' => '../uploads/emp_manual/my_attendance.png',
        'description' => 'This page shows your personal attendance history. Use it to verify your punch timing, daily status, and total work hours for each day.',
        'steps' => [
            'Open Attendance -> My Attendance from the menu.',
            'Use the available filters such as date, month, year, or office if you want to review a specific period.',
            'Click the filter or search button after selecting the required values so the table refreshes with your data.',
            'Check punch in, punch out, attendance status, and working hours carefully for each row.',
            'If any record is incorrect, open Apply Change and submit a correction request for that date.'
        ]
    ],
    [
        'title' => 'Team Attendance',
        'image' => '../uploads/emp_manual/team_attendance.png',
        'description' => 'This page is mainly for team leaders or employees who have permission to review attendance for their reporting team.',
        'steps' => [
            'Open Attendance -> Team Attendance.',
            'Select the employee, date, month, or other filters available on the page.',
            'Click the filter button to load the attendance report for the team member or date range you want to check.',
            'Review punch timing, attendance status, and work-hour details from the listing.',
            'This page is for monitoring and review. If a correction is needed, the employee should submit it through Apply Change.'
        ]
    ],
    [
        'title' => 'Break Records',
        'image' => '../uploads/emp_manual/break_records.png',
        'description' => 'This page shows the history of all recorded break entries. It helps you verify when each break started, ended, and how long it lasted.',
        'steps' => [
            'Open Attendance -> Break Records.',
            'Use the page filters if you need records for a specific date or period.',
            'Click filter or search to load the required data.',
            'Review the break start time, end time, and total break duration shown in the report.',
            'If something looks incorrect, use the attendance correction process to report it to the approving authority.'
        ]
    ],
    [
        'title' => 'Apply Change',
        'image' => '../uploads/emp_manual/apply_change_attendance.png',
        'description' => 'Use this page when your attendance needs correction, such as missing punch-in, wrong punch-out, or any attendance mismatch.',
        'steps' => [
            'Open Attendance -> Apply Change.',
            'Select the date for which the attendance correction is needed.',
            'Enter the correct details and write a clear reason explaining what was wrong and what should be updated.',
            'Click the submit button to send the request for approval.',
            'After submission, the request moves for review and you can later check its status in Change Requests.'
        ]
    ],
    [
        'title' => 'Change Requests',
        'image' => '../uploads/emp_manual/manage_change_attendance.png',
        'description' => 'This page helps you monitor all attendance change requests that you have already submitted.',
        'steps' => [
            'Open Attendance -> Change Requests.',
            'Review each request row to see the date, reason, request details, and current approval status.',
            'Check whether the request is pending, approved, or rejected.',
            'If remarks or comments are shown, read them to understand the final decision.',
            'Once a request is approved, the corrected attendance should reflect in your attendance records.'
        ]
    ],
    [
        'title' => 'Today Visits',
        'image' => '../uploads/emp_manual/today_visits.png',
        'description' => 'Use this page to manage visit activities planned for the current day. It is especially useful for field employees or sales teams who regularly record visits.',
        'steps' => [
            'Open Visits -> Today Visits.',
            'Review the visit entries scheduled or recorded for the current date.',
            'If the page provides add, edit, check-in, check-out, or status buttons, use them to update the visit at the proper stage.',
            'Fill in client details, remarks, status, or location information where required.',
            'After saving, the updated visit remains available for reports and follow-up activity.'
        ]
    ],
    [
        'title' => 'My Visits',
        'image' => '../uploads/emp_manual/my_visits.png',
        'description' => 'This page shows your complete visit history. Use it to review past visits, status updates, and client movement records.',
        'steps' => [
            'Open Visits -> My Visits.',
            'Use the available date or status filters to view a specific set of records.',
            'Click the filter button to load the required visit list.',
            'Review completed, pending, or follow-up visit entries carefully.',
            'Use this page whenever you need your own visit history for reporting, follow-up, or verification.'
        ]
    ],
    [
        'title' => 'Near Me',
        'image' => '../uploads/emp_manual/near_by_me.png',
        'description' => 'This page is used for location-based work support. It can help you identify nearby offices, clients, or relevant points based on your current location.',
        'steps' => [
            'Open Visits -> Near Me.',
            'If the browser asks for location access, click allow so the system can read your current location.',
            'Use the map or nearby listing shown on the page to identify useful places related to your work.',
            'Click any available location, direction, or visit-related action to proceed according to your workflow.',
            'This page is mainly for field usage and becomes more useful when location data is accurate.'
        ]
    ],
    [
        'title' => 'Expenses',
        'image' => '../uploads/emp_manual/expenses.png',
        'description' => 'Use the expenses page to create reimbursement requests and monitor their approval status.',
        'steps' => [
            'Open Expenses from the left menu.',
            'Click the add or submit expense button if you want to create a new claim.',
            'Enter the expense amount, date, category, description, and attachment if the form asks for supporting documents.',
            'Click submit or save to create the expense request.',
            'After submission, track the approval or rejection status from the expense listing on the same page.'
        ]
    ],
    [
        'title' => 'Daily Report',
        'image' => '../uploads/emp_manual/daily_report.png',
        'description' => 'This page is used to submit your daily work summary. It helps managers know what work was completed during the day.',
        'steps' => [
            'Open Work -> Daily Report.',
            'Click the add, create, or submit report button if a new entry form is shown separately.',
            'Enter the work completed, client activity, remarks, or progress details for the day.',
            'Click save or submit so the daily report is stored in the system.',
            'Update this page regularly so your reporting history remains complete and accurate.'
        ]
    ],
    [
        'title' => 'Assigned Task',
        'image' => '../uploads/emp_manual/assigned_task.png',
        'description' => 'Use this page to track all tasks assigned to you. It helps you understand deadlines, status, and action items.',
        'steps' => [
            'Open Work -> Assigned Task.',
            'Review the task title, due date, instructions, and status shown in the task list.',
            'Open a task or click the available action button to update progress, remarks, or completion status if the page supports it.',
            'Use filters or search if you need a specific task quickly.',
            'Keep task status updated so your manager can track actual progress.'
        ]
    ],
    [
        'title' => 'Worksheet',
        'image' => '../uploads/emp_manual/worksheet.png',
        'description' => 'The worksheet page is used to review structured work data assigned to you or recorded against your role. It may help in productivity review, reporting, or task planning.',
        'steps' => [
            'Open Work -> Worksheet.',
            'Use any available filter, date selector, or employee selector to narrow the report.',
            'Click filter or search to load the worksheet data.',
            'Review the entries, totals, or work lines shown on the page carefully.',
            'Use this page as a reference before updating task progress or reporting work completion.'
        ]
    ],
    [
        'title' => 'Manage Salary',
        'image' => '../uploads/emp_manual/manage_salary.png',
        'description' => 'This page helps you review your salary records, deduction details, and net salary from the employee side.',
        'steps' => [
            'Open Salary -> Manage Salary.',
            'Select the required month, year, or other available filters if you want to review a particular salary period.',
            'Click the filter or search button to load the salary record.',
            'Review gross salary, deductions, advance, tax, and final net salary carefully.',
            'Use this page before downloading salary slips or raising any payroll-related question.'
        ]
    ],
    [
        'title' => 'Monthly Advance',
        'image' => '../uploads/emp_manual/monthly_advance.png',
        'description' => 'Use this page to request an advance salary amount for a specific month when your company policy allows it.',
        'steps' => [
            'Open Salary -> Monthly Advance.',
            'Select the payroll month for which you want the advance amount.',
            'Enter the requested amount and type a clear reason for the request.',
            'Click the submit button to send the request to admin for approval.',
            'After submission, check the request list on the same page to see whether it is pending, approved, or rejected.'
        ]
    ],
    [
        'title' => 'Yearly Advance',
        'image' => '../uploads/emp_manual/yearly_advance.png',
        'description' => 'This page is used to request one advance amount for the full year instead of only one month. After approval, admin can allocate part of the amount into payroll month by month.',
        'steps' => [
            'Open Salary -> Yearly Advance.',
            'Select the required year and enter the total requested advance amount.',
            'Write a proper reason so admin can understand why the yearly advance is needed.',
            'Click submit yearly request to send it for approval.',
            'After approval, admin may allocate part of that amount into one or more payroll months, and the allocation will reflect in salary processing.'
        ]
    ],
    [
        'title' => 'My Team',
        'image' => '../uploads/emp_manual/my_team.png',
        'description' => 'This page shows the team structure connected to your role. It is mainly used to understand reporting hierarchy and team relationships.',
        'steps' => [
            'Open My Team from the sidebar.',
            'Review the hierarchy, reporting manager, or team members shown on the page.',
            'Click on any visible person or hierarchy item if the page supports deeper drill-down.',
            'Use this page mainly for reference and team visibility.',
            'This page usually does not require submission, but it helps you understand your team structure.'
        ]
    ],
    [
        'title' => 'Apply Leave',
        'image' => '../uploads/emp_manual/apply_leave.png',
        'description' => 'Use this page to submit leave applications and track approval results for your requested leave dates.',
        'steps' => [
            'Open Apply Leave from the sidebar.',
            'Click the add leave or apply leave button if the page first shows the request list.',
            'Choose the leave type, start date, end date, and reason for leave.',
            'Click submit to send the leave request for approval.',
            'After submission, review the request list to see whether the leave is pending, approved, or rejected.'
        ]
    ],
    [
        'title' => 'Tickets',
        'image' => '../uploads/emp_manual/tickets.png',
        'description' => 'The tickets page is used for internal issue reporting and support follow-up. Use it whenever you need help from the admin, IT, HR, or support team.',
        'steps' => [
            'Open Tickets from the menu.',
            'Click the create ticket, add ticket, or new ticket button to open the form.',
            'Enter a subject, category, and detailed issue description so the support team can understand the problem.',
            'Click submit or save to create the ticket.',
            'After submission, return to the ticket list to track progress, replies, and final resolution status.'
        ]
    ],
    [
        'title' => 'Leads',
        'image' => '../uploads/emp_manual/my_lead.png',
        'description' => 'The leads section helps you manage the leads assigned to you. It usually includes lead list, reminder tracking, and lead aging support in one menu.',
        'steps' => [
            'Open Leads from the sidebar, then choose My Leads, Follow-up Reminders, or Lead Aging depending on what you want to review.',
            'In My Leads, open the lead record and update stage, remarks, contact result, or follow-up date after each client interaction.',
            'In Follow-up Reminders, review which leads need action and click into the relevant lead to update it.',
            'In Lead Aging, check which leads have not moved for a long time and prioritize them for action.',
            'Regular updates in this section help keep the lead pipeline accurate and useful for reporting.'
        ]
    ],
    [
        'title' => 'Assigned Assets',
        'image' => '../uploads/emp_manual/assigned_assets.png',
        'description' => 'This page shows all company assets issued to you, such as laptop, mobile phone, accessories, or other official items.',
        'steps' => [
            'Open Assigned Assets from the sidebar.',
            'Review the asset name, issue date, serial information, and current assignment status shown in the list.',
            'Open any detailed view if the page provides a button for asset information or document review.',
            'Use this page as your official reference for items assigned in your name.',
            'If you find missing, wrong, or unreturned asset information, report it to the concerned admin immediately.'
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
    
    <h2 class="manual-title">Employee HRMS User Manual</h2>
    <p class="manual-intro">This page helps employees understand the main modules of the HRMS system step by step.</p>
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
