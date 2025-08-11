// Reception Check-out Functions
function loadCheckoutBookings() {
    const tbody = document.getElementById('checkout-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading check-out bookings...</td></tr>';

    fetch('reception_get_bookings.php?status=checked-in')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                checkoutBookings = data.bookings;
                renderCheckoutBookings(checkoutBookings);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading check-out bookings:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error loading bookings</td></tr>';
        });
}

function renderCheckoutBookings(bookings) {
    const tbody = document.getElementById('checkout-table-body');
    if (!tbody) return;

    if (!bookings || bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No check-out bookings found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    bookings.forEach(booking => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${booking.BookingID}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${booking.GuestName}</div>
                <div class="text-sm text-gray-500">${booking.GuestEmail}</div>
                <div class="text-sm text-gray-500">${booking.GuestPhone || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">Room ${booking.RoomNumber}</div>
                <div class="text-sm text-gray-500">${booking.RoomType}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckinDate_formatted}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckoutDate_formatted}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                $${parseFloat(booking.TotalAmount).toFixed(2)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="processCheckout(${booking.BookingID}, ${booking.TotalAmount})" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md mr-2">
                    <i class="fas fa-sign-out-alt mr-1"></i> Check-out
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function searchCheckoutBookings() {
    clearTimeout(checkoutSearchTimeout);
    checkoutSearchTimeout = setTimeout(() => {
        const bookingId = document.getElementById('checkout-booking-id').value.toLowerCase();
        const guestName = document.getElementById('checkout-guest-name').value.toLowerCase();

        const filtered = checkoutBookings.filter(booking => {
            const matchesId = !bookingId || booking.BookingID.toString().includes(bookingId);
            const matchesName = !guestName || booking.GuestName.toLowerCase().includes(guestName);
            return matchesId && matchesName;
        });

        renderCheckoutBookings(filtered);
    }, 300);
}

function processCheckout(bookingId, amount) {
    const paymentMethod = prompt('Payment method (cash/card/transfer):', 'cash');
    if (!paymentMethod) return;

    if (!confirm(`Confirm check-out and payment of $${amount}?`)) return;

    fetch('reception_update_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            bookingID: bookingId,
            action: 'checkout',
            paymentMethod: paymentMethod,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('Check-out Complete', 'Guest has been checked out and payment processed.');
            loadCheckoutBookings(); // Refresh list
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error processing check-out:', error);
        alert('Error processing check-out');
    });
}

// Room Status Functions
function loadRoomStatus() {
    const tbody = document.getElementById('room-status-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading room status...</td></tr>';

    fetch('reception_get_rooms.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roomStatuses = data.rooms;
                renderRoomStatus(data.rooms, data.summary);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading room status:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">Error loading room status</td></tr>';
        });
}

function renderRoomStatus(rooms, summary) {
    // Update summary
    if (summary) {
        document.getElementById('total-rooms').textContent = summary.total;
        document.getElementById('available-rooms').textContent = summary.available;
        document.getElementById('occupied-rooms').textContent = summary.occupied;
        document.getElementById('reserved-rooms').textContent = summary.reserved;
        document.getElementById('cleaning-rooms').textContent = summary.cleaning;
        document.getElementById('maintenance-rooms').textContent = summary.maintenance;
    }

    const tbody = document.getElementById('room-status-table-body');
    if (!tbody) return;

    if (!rooms || rooms.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No rooms found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    rooms.forEach(room => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        let guestInfo = '-';
        let checkoutDate = '-';
        if (room.GuestName) {
            guestInfo = room.GuestName;
            checkoutDate = room.CheckoutDate_formatted || '-';
        }

        let actionButtons = '';
        switch (room.DisplayStatus) {
            case 'available':
                actionButtons = `<button onclick="createCleanupRequest(${room.RoomID})" class="text-blue-600 hover:text-blue-900 text-xs mr-2">
                    <i class="fas fa-broom mr-1"></i> Clean
                </button>`;
                break;
            case 'cleaning':
                actionButtons = `<button onclick="updateCleanupStatus(${room.CleanupRequestID}, 'completed')" class="text-green-600 hover:text-green-900 text-xs mr-2">
                    <i class="fas fa-check mr-1"></i> Complete
                </button>`;
                break;
            case 'maintenance':
                actionButtons = `<button onclick="setRoomAvailable(${room.RoomID})" class="text-green-600 hover:text-green-900 text-xs mr-2">
                    <i class="fas fa-check mr-1"></i> Ready
                </button>`;
                break;
            default:
                actionButtons = '<span class="text-gray-400 text-xs">No actions</span>';
        }

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                <div class="flex items-center">
                    <i class="${room.StatusIcon} text-gray-400 mr-2"></i>
                    ${room.RoomNumber}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.RoomType}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${room.StatusClass}">
                    ${room.DisplayStatus}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${guestInfo}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkoutDate}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                ${actionButtons}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function createCleanupRequest(roomId) {
    if (!confirm('Create cleanup request for this room?')) return;

    fetch('reception_cleanup_requests.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ roomID: roomId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('Cleanup Request Created', 'Cleanup request has been created successfully.');
            loadRoomStatus(); // Refresh
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error creating cleanup request:', error);
        alert('Error creating cleanup request');
    });
}

// Cleanup Request Functions
function loadCleanupRequests() {
    const status = document.getElementById('cleanup-status-filter')?.value || 'all';
    const tbody = document.getElementById('cleanup-requests-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading cleanup requests...</td></tr>';

    fetch(`reception_cleanup_requests.php?status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cleanupRequests = data.requests;
                renderCleanupRequests(data.requests);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading cleanup requests:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">Error loading requests</td></tr>';
        });
}

function renderCleanupRequests(requests) {
    const tbody = document.getElementById('cleanup-requests-table-body');
    if (!tbody) return;

    if (!requests || requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No cleanup requests found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    requests.forEach(request => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        let actionButtons = '';
        switch (request.Status) {
            case 'pending':
                actionButtons = `
                    <button onclick="updateCleanupStatus(${request.CleanupRequestID}, 'in_progress')" class="text-blue-600 hover:text-blue-900 text-xs mr-2">
                        <i class="fas fa-play mr-1"></i> Start
                    </button>
                    <button onclick="updateCleanupStatus(${request.CleanupRequestID}, 'completed')" class="text-green-600 hover:text-green-900 text-xs">
                        <i class="fas fa-check mr-1"></i> Complete
                    </button>
                `;
                break;
            case 'in_progress':
                actionButtons = `
                    <button onclick="updateCleanupStatus(${request.CleanupRequestID}, 'completed')" class="text-green-600 hover:text-green-900 text-xs">
                        <i class="fas fa-check mr-1"></i> Complete
                    </button>
                `;
                break;
            default:
                actionButtons = '<span class="text-gray-400 text-xs">No actions</span>';
        }

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${request.CleanupRequestID}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">Room ${request.RoomNumber}</div>
                <div class="text-sm text-gray-500">${request.RoomType}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${request.LastGuestName || '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${request.RequestTime_formatted}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${request.StatusClass}">
                    ${request.Status.replace('_', ' ')}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                ${actionButtons}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateCleanupStatus(requestId, newStatus) {
    if (!confirm(`Change status to ${newStatus.replace('_', ' ')}?`)) return;

    fetch('reception_cleanup_requests.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            requestID: requestId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('Status Updated', `Cleanup request status updated to ${newStatus.replace('_', ' ')}.`);
            loadCleanupRequests(); // Refresh
            loadRoomStatus(); // Also refresh room status
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating cleanup status:', error);
        alert('Error updating status');
    });
}

function showCreateCleanupModal() {
    const roomNumber = prompt('Enter room number for cleanup request:');
    if (!roomNumber) return;
    alert('This feature requires room lookup by number - needs implementation');
}

// Compatibility functions for old code
function renderReceptionCheckinList() { loadCheckinBookings(); }
function renderReceptionCheckoutList() { loadCheckoutBookings(); }
function renderReceptionRoomStatus() { loadRoomStatus(); }