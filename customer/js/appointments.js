async function bookAppointment(serviceId, date, time) {
    try {
        const response = await fetch('book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                service_id: serviceId,
                date: date,
                time: time
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            alert(data.error || 'Failed to book appointment');
            return false;
        }

        alert('Appointment booked successfully!');
        return true;
    } catch (error) {
        alert('Error booking appointment. Please try again.');
        console.error('Booking error:', error);
        return false;
    }
} 