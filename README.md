# Barbershop Management System

A web-based barbershop management system that allows customers to book appointments, barbers to manage their schedules, and administrators to oversee operations.

## Features

- User registration and authentication
- Appointment booking system
- Service management
- Barber availability management
- Admin dashboard
- Customer dashboard
- Responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd barbershop
```

2. Create a `.env` file in the root directory and configure your environment variables:
```env
DB_HOST=localhost
DB_USER=your_database_user
DB_PASS=your_database_password
DB_NAME=your_database_name
BASE_URL=your_base_url
```

3. Import the database schema:
```bash
mysql -u your_username -p your_database_name < sql/schema.sql
```

4. Configure your web server to point to the project directory

5. Set proper permissions:
```bash
chmod 755 -R .
chmod 777 -R uploads/ # if you have an uploads directory
```

## Security Considerations

- All passwords are hashed using PHP's password_hash()
- SQL injection prevention using prepared statements
- XSS protection through proper output escaping
- CSRF protection implemented
- Secure session handling
- Input validation and sanitization

## Default Credentials

For security reasons, please change these credentials after first login:

- Admin:
  - Username: admin
  - Password: admin123

- Barber:
  - Username: barber
  - Password: barber123

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 