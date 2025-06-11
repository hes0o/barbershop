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

        let data;
        try {
            data = await response.json();
        } catch (e) {
            throw new Error('Server returned invalid response');
        }

        if (!response.ok) {
            throw new Error(data.error || 'Failed to book appointment');
        }
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to book appointment');
        }

        alert('Appointment booked successfully!');
        return true;
    } catch (error) {
        alert(error.message || 'Error booking appointment. Please try again.');
        console.error('Booking error:', error);
        return false;
    }
} 