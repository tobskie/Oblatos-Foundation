# Oblatos Foundation Donation Management System

A web-based donation management system for Oblatos Foundation that handles donor management, donation tracking, and receipt generation.

## Features

- User Management (Admin, Cashier, Donor roles)
- Secure Authentication System
- Donation Processing and Tracking
- Receipt Generation
- Donor Profile Management
- Administrative Dashboard
- Payment Method Integration (GCash, Bank Transfer)

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web Server (Apache/Nginx)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/oblatos-foundation.git
cd oblatos-foundation
```

2. Install dependencies:
```bash
composer install
```

3. Create configuration files:
```bash
cp config/database.example.php config/database.php
cp .env.example .env
```

4. Configure your environment:
- Edit `config/database.php` with your database credentials
- Update `.env` with your environment-specific settings

5. Set up the database:
```bash
php cli_run_migration.php
```

6. Set proper permissions:
```bash
chmod 755 -R ./
chmod 777 -R ./uploads
chmod 777 -R ./logs
```

## Directory Structure

- `/admin` - Administrative interface files
- `/cashier` - Cashier interface files
- `/donor` - Donor interface files
- `/config` - Configuration files
- `/includes` - Common PHP includes
- `/models` - Database models
- `/utils` - Utility functions
- `/uploads` - File upload directory
- `/assets` - CSS, JavaScript, and image files

## Security

- All sensitive credentials should be stored in `.env`
- Database backups are excluded from git
- Upload directories are protected
- Session management is implemented
- Input validation and sanitization

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please email support@oblatos.org or open an issue in the GitHub repository. 