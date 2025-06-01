# Oblatos Foundation Donation Management System

A web-based donation management system for Oblatos Foundation, built with PHP and MySQL.

## Features

- Multi-user roles (Admin, Cashier, Donor)
- Secure donation processing
- Real-time donation verification
- Donor tier management
- Email notifications
- Payment receipt management
- Donation history tracking

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP/WAMP/MAMP
- Composer (for dependencies)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/oblatos-foundation.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create a MySQL database named `oblatos_foundation`

4. Import the database structure:
   - Use the provided `migrations/create_tables.sql` file

5. Configure the application:
   - Copy `config/email_config.example.php` to `config/email_config.php`
   - Update the email configuration with your SMTP details

6. Set up the upload directories:
   ```bash
   mkdir -p uploads/receipts
   chmod 777 uploads/receipts
   ```

7. Create default admin account:
   ```bash
   php create_admin.php
   ```

## Configuration

1. Database Configuration:
   - Edit `config/database.php` with your database credentials

2. Email Configuration:
   - Edit `config/email_config.php` with your SMTP settings

3. Application Configuration:
   - Edit `config/config.php` for general settings

## Usage

1. Access the application through your web server
2. Log in with the default admin credentials:
   - Username: admin
   - Password: admin123 (change this immediately)

## Security

- Change default admin password after installation
- Keep config files secure
- Regular backups recommended
- Monitor logs for suspicious activity

## Directory Structure

```
oblatos-foundation/
├── admin/           # Admin panel files
├── cashier/         # Cashier panel files
├── config/          # Configuration files
├── donor/           # Donor panel files
├── includes/        # Common include files
├── migrations/      # Database migrations
├── models/          # Data models
├── uploads/         # File uploads
└── utils/          # Utility classes
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@oblatosfoundation.org or create an issue in the repository. 