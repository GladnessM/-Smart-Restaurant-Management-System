🍽️ DineEase Technologies – Smart Restaurant Management System

A comprehensive web-based restaurant management system designed to streamline operations. It allows customers to place orders digitally (e.g., via QR code) and enables restaurant staff to manage orders, tables, and menus in real-time through a centralized admin dashboard.

🚀 Features

📱 Customer Interface (Frontend)

Digital Menu: Browse food items with categories and prices.

QR Ordering: Place orders directly from the table without waiting for a waiter.

Real-time Status: View the status of orders (Pending, Cooking, Served).

🖥️ Admin Dashboard (Admin)

Live Order Management: View incoming orders instantly.

Status Workflow: Orders are automatically updated based on real-time inputs from the Kitchen and Bar interfaces.

Analytics: View total revenue, active orders, and active staff.

Responsive Design: Works on tablets and desktop POS systems (built with Tailwind CSS).

⚙️ Backend (API & Logic)

Database Connection: Secure PDO connection to MySQL.

CRUD Operations: Create, Read, Update, and Delete orders dynamically.

Session Management: Secure login for admin staff (future implementation).

📂 Project Structure

To keep collaboration organized, please ensure you work in your assigned directory:

/ (Root)
├── frontend/      # 👩🏽‍💻 Member A: Customer Interface (HTML, CSS, JS)
├── backend/       # 👨🏾‍💻 Member B: API Endpoints & Database Config (PHP)
│   └── db_connect.php  # Central database connection file
├── admin/         # 👩🏿‍💻 Member C: Admin Dashboard & System Integration
│   └── admin_dashboard.php
├── database/      # 🗄️ SQL files
│   └── schema.sql      # Run this to create the 'dineease_db' and tables
└── README.md      # Project Documentation


🛠️ Setup & Installation

Prerequisites

XAMPP / WAMP / MAMP (Apache & MySQL server)

A web browser (Chrome/Firefox/Edge)

Installation Steps

Clone the Repository

git clone [https://github.com/yourusername/smart-restaurant-system.git](https://github.com/yourusername/smart-restaurant-system.git)
cd smart-restaurant-system


Database Setup

Open phpMyAdmin (usually http://localhost/phpmyadmin).

Create a new database named dineease_db.

Import the database/schema.sql file.

Alternatively, run the SQL command:

CREATE DATABASE dineease_db;
-- (Then copy-paste the contents of schema.sql)


Configure Connection

Open backend/db_connect.php (or wherever your connection logic resides).

Ensure credentials match your local setup:

$host = 'localhost';
$dbname = 'dineease_db';
$username = 'root';
$password = ''; // Default for XAMPP is empty


Run the Application

Move the project folder to your server's root directory:

XAMPP: C:\xampp\htdocs\

MAMP: /Applications/MAMP/htdocs/

Open your browser and navigate to:
http://localhost/smart-restaurant-system/admin/admin_dashboard.php

🎨 Tech Stack

Frontend: HTML5, CSS3, JavaScript, Tailwind CSS (via CDN)

Backend: PHP (Vanilla)

Database: MySQL

Icons: FontAwesome

🤝 Contribution Guidelines

Pull the latest changes before starting work (git pull).

Work only in your assigned folder (frontend, backend, or admin).

Commit with clear messages (e.g., git commit -m "Added order status toggle logic").