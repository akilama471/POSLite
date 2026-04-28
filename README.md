# Nextgen Easy POS `new-code`

This folder contains the MVC rewrite of the legacy POS system from `old-code`.

## Requirements

- PHP 8.2 or newer
- MySQL or MariaDB
- Access to the existing legacy database used by the old system

## Project Structure

- `public/` - web root and front controller
- `bootstrap/app.php` - app bootstrap
- `controller/` - controllers
- `Models/` - database models
- `views/` - PHP views
- `middleware/` - auth and permission middleware

## Step-by-Step Run Instructions

### 1. Open a terminal in `new-code`

```powershell
cd "M:\Project\Nextgen Easy POS\new-code"
```

### 2. Create or update the `.env` file

The app reads database settings from `new-code/.env`.

Add these keys:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Notes:

- The current code expects the legacy database tables to already exist.
- Login uses the legacy `sys_user` table.
- Permissions and shops are also loaded from legacy tables.

### 3. Make sure your database is available

Start MySQL or MariaDB and confirm the database in `.env` is reachable.

### 4. Start the PHP development server

Run this from inside `new-code`:

```powershell
php -S localhost:8000 -t public
```

### 5. Open the application in your browser

Use:

```text
http://localhost:8000
```

You should be redirected to the login page or see the login screen directly.

### 6. Log in with an existing legacy user

- Username is matched against `sys_user.ankaya`
- Password is checked against the legacy SHA-1 hash in `sys_user.murapadaya`

## Available Routes

- `/`
- `/login`
- `/dashboard`
- `/settings`
- `/settings/users`
- `/settings/profile`
- `/settings/privileges`
- `/settings/user-privileges`
- `/settings/shops`
- `/categories`

## If You Want To Run With Apache

Point your virtual host document root to:

```text
new-code/public
```

The included `public/.htaccess` handles routing to `index.php`.

## Troubleshooting

### Blank page or server error

- Check that PHP 8.2+ is installed
- Check the terminal running `php -S`
- Confirm `public/index.php` is being used as the entrypoint

### Database connection error

- Recheck `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`
- Confirm MySQL is running
- Confirm the target database exists

### Login fails

- Confirm the user exists in `sys_user`
- Confirm the account has `statusu = 1`
- Confirm you are using the original legacy password

## Current Scope

The migrated `new-code` app currently covers:

- login
- dashboard
- settings
- user management
- privilege management
- shop management
- product categories
