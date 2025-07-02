// Sample data for rooms
// ... (toàn bộ nội dung JavaScript từ codeweb.html) ... 

// Reception Panel logic
function renderReceptionCheckinList() {
    const tbody = document.querySelector('#reception-checkin-tab table tbody');
    if (!tbody) return;
    // Lọc booking có trạng thái 'pending' hoặc 'confirmed' và chưa checked-in
    const checkinBookings = adminBookings.filter(b => b.status === 'pending' || b.status === 'confirmed');
    tbody.innerHTML = checkinBookings.length ? '' : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings to check-in</td></tr>`;
    checkinBookings.forEach(booking => {
        const checkInDate = new Date(booking.checkIn);
        const checkOutDate = new Date(booking.checkOut);
        tbody.innerHTML += `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.guestName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.roomName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">${booking.status.charAt(0).toUpperCase()+booking.status.slice(1)}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <button class="text-green-600 hover:text-green-900 font-medium" onclick="receptionCheckin('${booking.id}')">
                    <i class="fas fa-check-circle mr-1"></i> Check-in
                </button>
            </td>
        </tr>`;
    });
}

function receptionCheckin(bookingId) {
    const booking = adminBookings.find(b => b.id === bookingId);
    if (booking) {
        booking.status = 'checked-in';
        // Cập nhật trạng thái phòng
        const room = rooms.find(r => booking.roomName.includes(r.name));
        if (room) room.status = 'occupied';
        renderReceptionCheckinList();
        renderReceptionCheckoutList();
        renderReceptionRoomStatus();
        showSuccessModal('Check-in Successful', `Booking ${bookingId} has been checked in.`);
    }
}

function renderReceptionCheckoutList() {
    const tbody = document.querySelector('#reception-checkout-tab table tbody');
    if (!tbody) return;
    // Lọc booking đang checked-in
    const checkoutBookings = adminBookings.filter(b => b.status === 'checked-in');
    tbody.innerHTML = checkoutBookings.length ? '' : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No guests to check-out</td></tr>`;
    checkoutBookings.forEach(booking => {
        const checkInDate = new Date(booking.checkIn);
        const checkOutDate = new Date(booking.checkOut);
        tbody.innerHTML += `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.guestName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.roomName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Checked-in</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <button class="text-blue-600 hover:text-blue-900 font-medium" onclick="receptionCheckout('${booking.id}')">
                    <i class="fas fa-sign-out-alt mr-1"></i> Check-out
                </button>
            </td>
        </tr>`;
    });
}

function receptionCheckout(bookingId) {
    const booking = adminBookings.find(b => b.id === bookingId);
    if (booking) {
        booking.status = 'completed';
        // Cập nhật trạng thái phòng
        const room = rooms.find(r => booking.roomName.includes(r.name));
        if (room) room.status = 'dirty';
        renderReceptionCheckoutList();
        renderReceptionRoomStatus();
        showSuccessModal('Check-out Successful', `Booking ${bookingId} has been checked out.`);
    }
}

function renderReceptionRoomStatus() {
    const tbody = document.querySelector('#reception-status-tab table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    rooms.forEach(room => {
        let statusBadge = '';
        let cleanBtn = '';
        if (room.status === 'occupied') {
            statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Occupied</span>';
            cleanBtn = '<button class="text-gray-400 text-xs cursor-not-allowed" disabled><i class="fas fa-broom mr-1"></i> Do Not Disturb</button>';
        } else if (room.status === 'dirty') {
            statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dirty</span>';
            cleanBtn = `<button class="text-blue-600 hover:text-blue-900 text-xs" onclick="receptionCleanRoom(${room.id})"><i class="fas fa-broom mr-1"></i> Clean</button>`;
        } else if (room.status === 'maintenance') {
            statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Maintenance</span>';
            cleanBtn = '';
        } else {
            statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>';
            cleanBtn = `<button class="text-blue-600 hover:text-blue-900 text-xs" onclick="receptionCleanRoom(${room.id})"><i class="fas fa-broom mr-1"></i> Clean</button>`;
        }
        tbody.innerHTML += `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.type.charAt(0).toUpperCase()+room.type.slice(1)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${cleanBtn}</td>
        </tr>`;
    });
}

function receptionCleanRoom(roomId) {
    const room = rooms.find(r => r.id == roomId);
    if (room && room.status === 'dirty') {
        room.status = 'available';
        renderReceptionRoomStatus();
        showSuccessModal('Room Cleaned', `Room ${roomId} is now available.`);
    }
}

// Khi chuyển tab lễ tân, gọi render tương ứng
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('reception-checkin-tab')) renderReceptionCheckinList();
    if (document.getElementById('reception-checkout-tab')) renderReceptionCheckoutList();
    if (document.getElementById('reception-status-tab')) renderReceptionRoomStatus();
}); 