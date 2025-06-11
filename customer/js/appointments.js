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
        throw error;
    }
} 