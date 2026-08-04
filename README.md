# Admin Dashboard

This project contains a full-stack application with:
- a Laravel backend for APIs and business logic
- a React + Vite frontend for the admin dashboard

## Project structure

- backend/: Laravel API and database setup
- frontend/: React application with Vite

## Prerequisites

Before starting, make sure you have installed:
- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL or another supported database

## Backend setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Frontend setup

```bash
cd frontend
npm install
npm run dev
```

## Build for production

```bash
cd frontend
npm run build
```

## GitHub publishing

1. Initialize Git if needed:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```
2. Create a repository on GitHub.
3. Link the remote repository:
   ```bash
   git remote add origin <your-github-repo-url>
   git branch -M main
   git push -u origin main
   ```

## Notes

- Keep your environment variables in the .env files and do not commit them.
- The repository already includes a .gitignore to avoid uploading sensitive or generated files.
