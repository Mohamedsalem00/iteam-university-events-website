// Function to fetch events from the API
async function fetchEvents() {
    try {
        // For direct implementation with your API data:
        const apiData = [{
            "success": true,
            "events": [
                {
                    "event_id": 3,
                    "title": "Tech Career Fair",
                    "location": "University Main Campus",
                    "event_type": "fair",
                    "start_date": "2025-07-10 10:00:00",
                    "end_date": "2025-07-10 15:00:00",
                    "thumbnail_url": null,
                    "organizer_name": "Tech Innovators",
                    "total_attendees": 0,
                    "status": "upcoming"
                },
                {
                    "event_id": 2,
                    "title": "Digital Marketing Conference",
                    "location": "Grand Conference Hall",
                    "event_type": "conference",
                    "start_date": "2025-06-20 08:30:00",
                    "end_date": "2025-06-21 17:00:00",
                    "thumbnail_url": null,
                    "organizer_name": "Event Masters",
                    "total_attendees": 1,
                    "status": "upcoming"
                },
                {
                    "event_id": 1,
                    "title": "Web Development Workshop",
                    "location": "Room 101, Technology Building",
                    "event_type": "workshop",
                    "start_date": "2025-05-15 09:00:00",
                    "end_date": "2025-05-15 16:00:00",
                    "thumbnail_url": null,
                    "organizer_name": "Tech Innovators",
                    "total_attendees": 2,
                    "status": "upcoming"
                },
                {
                    "event_id": 4,
                    "title": "Data Science Webinar",
                    "location": "Online",
                    "event_type": "webinar",
                    "start_date": "2025-05-05 14:00:00",
                    "end_date": "2025-05-05 16:00:00",
                    "thumbnail_url": null,
                    "organizer_name": "Event Masters",
                    "total_attendees": 0,
                    "status": "completed"
                }
            ],
            "pagination": {
                "total_items": 4,
                "current_page": 1,
                "items_per_page": 10,
                "total_pages": 1,
                "start_item": 1,
                "end_item": 4
            }
        }];
        
        const data = apiData[0];
        
        if (data && data.success && data.events) {
            displayUpcomingEvents(data.events);
            initializeCalendar(data.events);
        } else {
            console.error('Invalid data structure or no events found', data);
            displayErrorMessage('No events to display');
        }
    } catch (error) {
        console.error('Error:', error);
        displayErrorMessage('Error loading events');
    }
}

// Function to initialize FullCalendar
function initializeCalendar(events) {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('Calendar element not found');
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false, // We're using custom header
        events: events.map(event => ({
            id: event.event_id,
            title: event.title,
            start: event.start_date,
            end: event.end_date,
            extendedProps: {
                type: event.event_type,
                location: event.location,
                organizer: event.organizer_name,
                status: event.status
            }
        })),
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventDidMount: function(info) {
            // Add tooltip
            info.el.title = info.event.title;
        },
        eventContent: function(arg) {
            return {
                html: `
                    <div class="p-1.5 rounded ${getEventTypeColor(arg.event.extendedProps.type)}">
                        <p class="text-xs font-medium text-white mb-px">${arg.event.title}</p>
                        <span class="text-xs text-white/80">${formatTime(arg.event.start)}</span>
                    </div>
                `
            };
        }
    });

    calendar.render();

    // Update current month display
    updateMonthDisplay(calendar.getDate());

    // Event listeners for navigation
    document.getElementById('prevMonth').addEventListener('click', () => {
        calendar.prev();
        updateMonthDisplay(calendar.getDate());
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        calendar.next();
        updateMonthDisplay(calendar.getDate());
    });

    // View switching
    document.querySelectorAll('[data-view]').forEach(button => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            calendar.changeView(view);
            
            // Update active state
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.classList.remove('bg-indigo-600', 'text-white');
                btn.classList.add('bg-indigo-50', 'text-indigo-600');
            });
            button.classList.remove('bg-indigo-50', 'text-indigo-600');
            button.classList.add('bg-indigo-600', 'text-white');
        });
    });
}

function getEventTypeColor(type) {
    const colors = {
        'meeting': 'bg-purple-600',
        'workshop': 'bg-sky-600',
        'seminar': 'bg-emerald-600',
        'conference': 'bg-indigo-600',
        'social': 'bg-pink-600',
        'fair': 'bg-orange-600',
        'webinar': 'bg-teal-600',
        'default': 'bg-indigo-600'
    };
    return colors[type?.toLowerCase()] || colors.default;
}

function formatTime(date) {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function updateMonthDisplay(date) {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
}

// Function to display error message in the events container
function displayErrorMessage(message) {
    const upcomingEventsContainer = document.querySelector('.col-span-12.xl\\:col-span-5 .flex.gap-5.flex-col');
    upcomingEventsContainer.innerHTML = `
        <div class="p-6 rounded-xl bg-white">
            <p class="text-center text-gray-500">${message}</p>
        </div>
    `;
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Function to display upcoming events in the sidebar
function displayUpcomingEvents(events) {
    const upcomingEventsContainer = document.getElementById('upcomingEvents');
    if (!upcomingEventsContainer) {
        console.error('Upcoming events container not found');
        return;
    }
    
    // Filter for upcoming events only
    const upcomingEvents = events.filter(event => event.status === 'upcoming');
    
    if (upcomingEvents.length === 0) {
        upcomingEventsContainer.innerHTML = `
            <div class="p-6 rounded-xl bg-white">
                <p class="text-center text-gray-500">No upcoming events</p>
            </div>
        `;
        return;
    }
    
    upcomingEventsContainer.innerHTML = upcomingEvents.slice(0, 3).map(event => `
        <div class="p-6 rounded-xl bg-white">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full ${getEventTypeColor(event.event_type)}"></span>
                    <p class="text-base font-medium text-gray-900">${formatDate(event.start_date)} - ${formatTime(event.start_date)}</p>
                    </div>
                    <div class="dropdown relative inline-flex">
                    <button type="button" class="dropdown-toggle inline-flex justify-center py-2.5 px-1 items-center gap-2 text-sm text-black rounded-full cursor-pointer font-semibold text-center shadow-xs transition-all duration-500 hover:text-indigo-600">
                        <i class="ri-more-2-fill"></i>
                        </button>
                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white border border-gray-200">
                        <div class="py-1">
                            <a href="edit-event.html?id=${event.event_id}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                            <button onclick="deleteEvent(${event.event_id})" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Delete</button>
                        </div>
                        </div>
                    </div>
                </div>
                <h6 class="text-xl leading-8 font-semibold text-black mb-1">${event.title}</h6>
            <p class="text-base font-normal text-gray-600">${event.location || 'No location specified'}</p>
            <div class="mt-2 flex items-center text-sm text-gray-500">
                <i class="ri-user-line mr-1"></i>
                <span>${event.organizer_name || 'No organizer specified'}</span>
            </div>
        </div>
    `).join('');

    // Initialize dropdowns
    initDropdowns();
}

// Initialize dropdowns function
function initDropdowns() {
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-target');
            const dropdownMenu = document.getElementById(target);
            
            if (!dropdownMenu) return;
            
            // Toggle the dropdown
            if (dropdownMenu.classList.contains('hidden')) {
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
                
                // Open this dropdown
                dropdownMenu.classList.remove('hidden');
            } else {
                dropdownMenu.classList.add('hidden');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
}

// Call the functions when the page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded, initializing calendar");
    fetchEvents();
});