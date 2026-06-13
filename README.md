# 🛡️ Community Hazard Report System

A web-based hazard reporting platform that allows civilians to report environmental and community hazards, officers to investigate and manage cases, and admins to oversee the entire system. Built with PHP, MySQL, and integrated with Firebase Firestore and Google Gemini AI.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Prerequisites](#-prerequisites)
- [Installation & Setup](#-installation--setup)
- [Project Structure](#-project-structure)
- [User Roles](#-user-roles)
- [API Endpoints](#-api-endpoints)

---

## ✨ Features

- **Civilian Dashboard** — Submit hazard reports with photos, GPS location, and descriptions
- **Officer Dashboard** — Investigate assigned cases, publish awareness alerts, update case status
- **Admin Dashboard** — Manage users, assign cases to officers, view analytics, export reports
- **Community Map** — Interactive map showing all reported hazards with pins
- **AI Chatbot** — Gemini-powered chatbot for hazard-related guidance
- **Report Tracking** — Civilians can track their submitted reports in real-time
- **Feedback System** — Rate and comment on resolved cases
- **Firebase Sync** — Firestore integration for real-time data synchronisation
- **Draft Saving** — Save report drafts and continue later

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 7.4+ |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript |
| Server | Apache (XAMPP) |
| Cloud DB | Firebase Firestore |
| AI | Google Gemini API |
| Dependencies | Composer |

---

## 📦 Prerequisites

Before you begin, make sure you have:

1. **XAMPP** (includes Apache, MySQL, PHP) — [Download here](https://www.apachefriends.org/)
2. **Composer** (PHP dependency manager) — [Download here](https://getcomposer.org/download/)
3. **A Google Gemini API Key** — [Get one here](https://aistudio.google.com/apikey)
4. **A Firebase Project** with Firestore enabled — [Firebase Console](https://console.firebase.google.com/)

---

## 🚀 Installation & Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/nrarfadnia2005-lab/community-hazard-report.git
```

Copy or move the cloned folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\community_hazard_report
```

### Step 2: Install PHP Dependencies

Open a terminal in the project folder and run:

```bash
composer install
```

This will install the Firebase PHP SDK and other required packages.

### Step 3: Set Up the MySQL Database

1. Start **XAMPP** and ensure **Apache** and **MySQL** are running
2. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
3. Create a new database called **`hazard`**
4. Click on the **Import** tab
5. Select the file `config/hazard.sql` from this project
6. Click **Go** to import all tables

### Step 4: Create the Gemini API Key Config

Create a new file at `config/gemini.php` with the following content:

```php
<?php
// Gemini API key for the chatbot
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
?>
```

Replace `YOUR_GEMINI_API_KEY_HERE` with your actual [Google Gemini API key](https://aistudio.google.com/apikey).

### Step 5: Set Up Firebase Credentials

1. Go to your [Firebase Console](https://console.firebase.google.com/) → Project Settings → Service Accounts
2. Click **"Generate new private key"** to download a JSON credentials file
3. Rename the file to `firebase_credentials.json`
4. Place it in the `config/` folder:

```
config/firebase_credentials.json
```

### Step 6: Create Upload Directories

These folders are created automatically when users upload files, but you can create them manually:

```bash
mkdir uploads/reports
mkdir uploads/alerts
```

### Step 7: Run the Application

1. Make sure **XAMPP Apache and MySQL** are running
2. Open your browser and go to:

```
http://localhost/community_hazard_report/
```

---

## 📁 Project Structure

```
community_hazard_report/
├── admin/                    # Admin dashboard HTML
├── api/                      # Backend PHP API
│   ├── admin/                # Admin endpoints (analytics, assign, ban, etc.)
│   ├── auth/                 # Authentication (login, register, profile)
│   ├── feedback/             # Feedback submission
│   ├── map/                  # Map pins and duplicate checking
│   ├── officer/              # Officer endpoints (alerts, cases)
│   ├── reports/              # Report CRUD, drafts, tracking
│   ├── chatbot.php           # Gemini AI chatbot
│   └── locations.php         # Location data
├── assets/                   # Images and static files
├── civilian/                 # Civilian dashboard HTML
├── config/                   # Configuration files
│   ├── db.php                # MySQL database connection
│   ├── firebase.php          # Firebase Firestore client
│   ├── gemini.php            # ⚠️ Create this file (see Step 4)
│   ├── firebase_credentials.json  # ⚠️ Add this file (see Step 5)
│   └── hazard.sql            # Database schema (import this)
├── includes/                 # Shared PHP helpers
│   ├── helpers.php           # Utility functions
│   └── session.php           # Session management
├── officer/                  # Officer dashboard HTML
├── uploads/                  # User-uploaded files (auto-created)
├── vendor/                   # Composer dependencies (auto-created)
├── index.html                # Landing page / Login page
├── composer.json             # PHP dependency config
└── .gitignore                # Git ignore rules
```

---

## 👥 User Roles

| Role | Capabilities |
|------|-------------|
| **Civilian** | Submit reports, track status, view community map, rate resolved cases, use AI chatbot |
| **Officer** | View assigned cases, update case status, publish awareness alerts, add evidence |
| **Admin** | Assign cases to officers, manage users (ban/delete), view analytics, export CSV reports |

---

## 🔌 API Endpoints

### Authentication (`/api/auth/`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `login.php` | POST | User login |
| `register.php` | POST | Civilian registration |
| `logout.php` | POST | Logout and destroy session |
| `profile.php` | GET | Get current user profile |
| `update_profile.php` | POST | Update user profile |
| `forgot_password.php` | POST | Password recovery |

### Reports (`/api/reports/`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `create.php` | POST | Submit a new hazard report |
| `list.php` | GET | List user's reports |
| `details.php` | GET | Get report details |
| `track.php` | GET | Track report status |
| `community_list.php` | GET | List all community reports |
| `draft_save.php` | POST | Save a report draft |
| `draft_load.php` | GET | Load saved draft |
| `append_evidence.php` | POST | Add evidence to a report |
| `count_by_district.php` | GET | Report count per district |

### Officer (`/api/officer/`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `my_cases.php` | GET | View assigned cases |
| `update_case.php` | POST | Update case status |
| `create_alert.php` | POST | Publish awareness alert |
| `delete_alert.php` | POST | Delete an alert |

### Admin (`/api/admin/`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `assign_case.php` | POST | Assign case to officer |
| `dashboard_stats.php` | GET | Dashboard statistics |
| `analytics.php` | GET | Detailed analytics |
| `users.php` | GET | List all users |
| `ban_user.php` | POST | Ban/unban a user |
| `delete_user.php` | POST | Delete a user |
| `delete_report.php` | POST | Delete a report |
| `create_officer.php` | POST | Register a new officer |
| `export_csv.php` | GET | Export reports as CSV |

---

## ⚠️ Important Notes

- The `config/gemini.php` and `config/firebase_credentials.json` files are **not included** in this repo for security. You must create them yourself (see Steps 4 & 5).
- The `vendor/` directory is excluded. Run `composer install` to install dependencies.
- The database connection in `config/db.php` uses `root` with no password (default XAMPP setup). Modify if your MySQL has different credentials.

---

## 📄 License

This project was developed as part of an academic assignment.
