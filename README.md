# 📅 Appointment Scheduler

A Laravel-based appointment scheduling system with both **Web UI (Blade/Livewire)**
and **REST API**, supporting slot-based booking, client auto-creation, and timezone-safe scheduling.

---

## 🚀 Features

- Create / view / manage appointments
- Slot-based scheduling (08:00–18:00)
- Prevent double booking
- Auto-create or resolve clients by EGN
- Validation for working hours and full-hour slots
- UTC storage with formatted output

---

## 📦 Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

npm install
npm run dev
php artisan serve
```

---

## 🌐 Web Routes

- GET /appointments → list
- GET /appointments/{id} → show
- GET /appointments/{id}/edit → edit
- DELETE /appointments/{id} → delete

---

## 🔌 API Routes

Base URL: /api

- GET /api/appointments
- POST /api/appointments
- GET /api/appointments/{id}
- PUT /api/appointments/{id}
- DELETE /api/appointments/{id}

---

## 📥 Example API Requests

### GET /api/appointments - List with existing appointments
Optional filters:
per_page: 20
page: 3
egn: 9001012244
from: 2026-06-03
to: 2026-06-30

### POST /api/appointments - Store

{
  "date": "2026-06-10", (required)
  "time": "14:00", (required)
  "first_name": "Ivan", (required)
  "last_name": "Petrov", (required)
  "egn": "9001011234", (required)
  "description": "Appointment description", (optional)
  "notification_type": "email" (sms|email)
}

---

## ⛔ Business Rules

- Only full-hour slots allowed
- Working hours: 08:00–18:00
- No past bookings
- No double booking
- UTC storage for all appointments
- Clients resolved by EGN

---

## 🧠 Tech Stack

- Laravel
- MySQL
- Blade / Livewire
- REST API
- Carbon
