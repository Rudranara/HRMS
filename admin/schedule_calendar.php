<?php
// Database Connection
include("header.php"); // Include your header

?>
<!-- FullCalendar CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<!-- Bootstrap CSS -->
<style>
.schedule-calendar-page {
    padding-bottom: 1.5rem;
}

.schedule-calendar-topbar,
.schedule-calendar-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.schedule-calendar-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.schedule-calendar-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.schedule-calendar-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.schedule-calendar-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.schedule-calendar-card {
    padding: 1.25rem;
    overflow: hidden;
    background:
        radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 26%),
        linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

#calendar {
    min-height: 760px;
}

.fc .fc-toolbar {
    gap: 1rem;
    margin-bottom: 1.15rem !important;
    padding: 0.2rem;
    flex-wrap: wrap;
}

.fc .fc-toolbar-chunk {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.fc .fc-toolbar-title {
    color: #0f172a;
    font-size: 1.45rem !important;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.fc .fc-button {
    min-height: 44px;
    min-width: 72px;
    padding: 0.7rem 1.35rem;
    border-radius: 16px !important;
    border: none !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    color: #fff !important;
    box-shadow: 0 14px 24px rgba(15, 23, 42, 0.16);
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    text-transform: capitalize;
}

.fc .fc-button-group {
    display: flex;
    gap: 0.65rem;
}

.fc .fc-button:hover,
.fc .fc-button:focus {
    background: linear-gradient(135deg, #111827 0%, #334155 100%) !important;
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.18) !important;
    transform: translateY(-1px);
}

.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background: linear-gradient(135deg, #163b72 0%, #2457a5 100%) !important;
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22) !important;
}

.fc-theme-standard .fc-scrollgrid,
.fc-theme-standard td,
.fc-theme-standard th {
    border-color: #e8edf3 !important;
}

.fc-theme-standard .fc-scrollgrid {
    border: 1px solid #e8edf3 !important;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
}

.fc .fc-col-header-cell {
    background: linear-gradient(180deg, #f8fbff 0%, #f4f8fc 100%);
    padding: 0.55rem 0;
}

.fc .fc-col-header-cell-cushion {
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
}

.fc .fc-daygrid-day-frame {
    min-height: 138px;
    padding: 0.35rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.92) 100%);
}

.fc .fc-daygrid-day-number,
.fc .fc-timegrid-axis-cushion,
.fc .fc-timegrid-slot-label-cushion {
    color: #475569;
    text-decoration: none;
    font-weight: 700;
}

.fc .fc-daygrid-day-number {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    font-size: 0.9rem;
}

.fc .fc-day-other .fc-daygrid-day-number {
    color: #c0c7d2;
}

.fc .fc-daygrid-day.fc-day-today,
.fc .fc-timegrid-col.fc-day-today {
    background: linear-gradient(180deg, rgba(219, 234, 254, 0.52) 0%, rgba(239, 246, 255, 0.7) 100%) !important;
}

.fc .fc-day-today .fc-daygrid-day-number {
    background: linear-gradient(135deg, #163b72 0%, #2457a5 100%);
    color: #fff;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
}

.fc .fc-daygrid-event,
.fc .fc-timegrid-event {
    border: none !important;
    border-radius: 14px !important;
    padding: 0.28rem 0.55rem !important;
    font-size: 0.76rem !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
}

.fc .fc-event-title,
.fc .fc-event-time {
    font-weight: 700;
}

.fc .fc-daygrid-more-link {
    color: #2457a5;
    font-weight: 700;
}

.fc .fc-scrollgrid-section-sticky > * {
    background: transparent;
}

.schedule-calendar-modal .modal-dialog {
    max-width: 560px;
}

.schedule-calendar-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.schedule-calendar-modal .modal-header,
.schedule-calendar-modal .modal-footer {
    border-color: #eef2f7;
    padding: 1rem 1.25rem;
}

.schedule-calendar-modal .modal-body {
    padding: 1.25rem;
}

.schedule-calendar-modal .modal-title {
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.schedule-calendar-modal .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.schedule-calendar-modal .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.schedule-calendar-modal .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.schedule-calendar-modal .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
}

.schedule-calendar-modal .btn-danger {
    background: #fbe6e5;
    color: #c24141;
    border: 1px solid #f4c9c7;
}

.schedule-calendar-modal .btn-secondary {
    background: #f3f4f6;
    color: #334155;
    border: none;
}

@media (max-width: 767.98px) {
    .schedule-calendar-card {
        padding: 1rem;
    }

    #calendar {
        min-height: 680px;
    }
}
</style>

<div class="container-fluid py-4 schedule-calendar-page">
    <div class="row">
        <div class="col-12">
            <div class="schedule-calendar-topbar">
                <span class="schedule-calendar-section-label">Calendar Planner</span>
                <h2 class="schedule-calendar-title">Schedule Calendar</h2>
                <p class="schedule-calendar-copy">Plan weekly offs and holidays, then manage events directly from the calendar.</p>
            </div>
        </div>
        <div class="col-12">
            <div class="schedule-calendar-card">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>
<!-- Add/Edit Event Modal -->
<div class="modal fade schedule-calendar-modal" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="eventForm">
                <input type="hidden" id="eventId" name="id"> <!-- For editing -->
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Event Title</label>
                        <input type="text" class="form-control" id="eventTitle" name="title" placeholder="Enter event title" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventType" class="form-label">Event Type</label>
                        <select class="form-control" id="eventType" name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="weekly_off">Weekly Off</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="startDate" class="form-label">Event Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display:none;">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: 'fetch_events', // Fetch events from the server
            dateClick: function (info) {
                // Reset form for adding a new event
                $('#eventForm')[0].reset();
                $('#eventId').val('');
                $('#deleteEventBtn').hide();
                $('#startDate').val(info.dateStr);
                $('#eventModalLabel').text('Add Event');
                $('#eventModal').modal('show');
            },
            eventClick: function (info) {
                // Populate modal with event data for editing
                $('#eventId').val(info.event.id);
                $('#eventTitle').val(info.event.title);
                $('#eventType').val(info.event.extendedProps.event_type);
                $('#startDate').val(info.event.startStr);
                $('#deleteEventBtn').show();
                $('#eventModalLabel').text('Edit Event');
                $('#eventModal').modal('show');
            }
        });
        calendar.render();

        // Handle Add/Edit Event Form Submission
        $('#eventForm').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = $('#eventId').val() ? 'update_event' : 'add_event';

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    alert(response);
                    $('#eventModal').modal('hide');
                    calendar.refetchEvents();
                },
                error: function () {
                    alert('Failed to save event. Please try again.');
                }
            });
        });

        // Handle Event Deletion
        $('#deleteEventBtn').on('click', function () {
            if (confirm('Are you sure you want to delete this event?')) {
                var eventId = $('#eventId').val();
                $.ajax({
                    url: 'delete_event',
                    type: 'POST',
                    data: { id: eventId },
                    success: function (response) {
                        alert(response);
                        $('#eventModal').modal('hide');
                        calendar.refetchEvents();
                    },
                    error: function () {
                        alert('Failed to delete event. Please try again.');
                    }
                });
            }
        });
    });
</script>
<?php include("footer.php") ?>
