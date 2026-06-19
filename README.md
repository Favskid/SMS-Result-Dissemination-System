# FULafia Student Result Dissemination System

A PHP and MySQL web application designed for Federal University of Lafia (FULafia). This system allows administrative staff to manage student data, upload academic results, and disseminate those results directly to students via SMS using the Twilio API.

## Features
- **Student Portal**: Students can enter their matriculation number to view or receive their results via SMS.
- **Admin Dashboard**: View total students, SMS sent/failed, and overall statistics.
- **Student Management**: Add and delete student records.
- **Result Management**: Upload result entries (supports CSV imports) and manually add results.
- **SMS Dissemination**: Automatically compile results and text them to students.
- **Role-based Authentication**: Secure admin and staff login.

## Requirements
- PHP 8.x
- MySQL / MariaDB (XAMPP recommended)
- Composer
- Twilio Account (for SMS)

## Setup Instructions

1. **Install Dependencies**
   Open your terminal in the project directory and run:
   ```bash
   composer install
   ```

2. **Database Setup**
   - Create a database in phpMyAdmin named `student_results`.
   - Import the `database.sql` file provided in this repository.

3. **Configuration**
   Open `config.php` and update your Twilio API keys if you wish to use the SMS functionality. Ensure `BASE_URL` aligns with your local setup.

4. **Access the System**
   - **Student Portal**: `http://localhost/student-results/index.php`
   - **Admin Portal**: `http://localhost/student-results/admin/login.php`
   
   *(Default Admin Demo Credentials if using the provided SQL sample data: Username: `admin`, Password: `admin123`)*

## License
Open-source project built for an academic final year project.
