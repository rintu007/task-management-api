# Task Management System - Backend API

Laravel 11+ REST API for task management with role-based access control, caching, and background jobs.

## 🚀 Quick Setup

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL/SQLite

### Installation
```bash
# Clone repository
git clone https://github.com/rintu007/task-management-api.git
cd task-management-api

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=

# Setup database
php artisan migrate
php artisan db:seed

# Generate Sanctum keys
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Start server
php artisan serve
```

## 👥 Default Users
- **Admin**: admin@example.com / password
- **User**: john@example.com / password

## 🧪 Testing
```bash
php artisan test
php artisan test --filter TaskTest
php artisan test --filter AuthTest
```

## 📚 API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login  
- `POST /api/logout` - User logout
- `GET /api/user` - Get current user

### Tasks
- `GET /api/tasks` - List tasks (filter by status)
- `POST /api/tasks` - Create task
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `GET /api/tasks/counts` - Task statistics

## 🔧 Features
- Role-based access control (Admin/User)
- Task CRUD with validation
- Redis/file caching system
- Background job processing
- XSS protection & security headers
- Request logging middleware
- Comprehensive test suite

## 🔔 Queue Setup
```bash
# Process background jobs (notifications)
php artisan queue:work
```

## 🚀 Production
```bash
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan queue:work --daemon
```

## 🐛 Troubleshooting
```bash
php artisan cache:clear
php artisan route:clear
tail -f storage/logs/laravel.log
```

---
