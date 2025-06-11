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

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        
        if (!data.success) {
            // If booking fails, get debug information
            const debugResponse = await fetch('debug_booking.php', {
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
            
            const debugData = await debugResponse.json();
            console.error('Booking failed. Debug information:', debugData);
            
            throw new Error(data.error || 'Failed to book appointment');
        }

        return data;
    } catch (error) {
        console.error('Error booking appointment:', error);
        // Show error to user
        alert(error.message || 'Failed to book appointment. Please try again.');
        throw error;
    }
} 