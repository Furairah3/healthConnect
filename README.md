# HealthConnect – Rural Telehealth Platform

## Project Overview
HealthConnect is a web-based telehealth platform designed to improve access to basic healthcare services for people living in rural and underserved communities. The system allows patients to submit health concerns online and receive medical guidance from trained healthcare volunteers and verified medical doctors without the need to travel long distances to hospitals or clinics.

The platform supports remote consultations, structured medical triage, health education, and case escalation within a secure and responsive web application. HealthConnect aims to reduce congestion in physical healthcare facilities while promoting early medical intervention and informed healthcare decisions.

---

## Course Information
- **Course:** CS341 – Web Technologies  
- **Semester:** Spring 2025  
- **Student:** Foureiratou ZAKARI YAOU IDI  
- **Lecturers:** David Sampah & Kwadwo Gyamfi Osafo-Maafo  

---

## Live Application
🔗 **Live URL:**  
http://169.239.251.102:341/~foureiratou.idi/healthConnect

>  The server will remain active until the end of January 2026 as required.

---

## Key Features
- Secure user authentication and role-based access control
- Patient health request creation and tracking
- Volunteer and doctor response system
- Case escalation from volunteers to doctors
- Doctor verification through certificate uploads
- Admin approval and monitoring dashboard
- Health education and medical tips
- Terms of Service and Privacy Policy enforcement

---

## User Roles
- **Patient:** Submit and track health requests
- **Volunteer:** Respond to non-critical cases and escalate when necessary
- **Doctor:** Handle complex cases and provide verified medical advice
- **Administrator:** Approve doctors, manage users, and monitor system activity

---

## System Architecture
HealthConnect follows a **3-Tier Architecture**:

### 1. Presentation Layer (Frontend)
- HTML5  
- CSS3  
- Bootstrap 5  
- JavaScript  

Responsible for user interface, form validation, and responsive design.

### 2. Application Layer (Backend)
- PHP 8+
- PDO

Handles business logic, authentication, authorization, and CRUD operations.

### 3. Data Layer (Database)
- MySQL

Stores user records, health requests, activity logs, and verification data using relational constraints.

---

## Security Measures
- Password hashing using `password_hash()` (bcrypt)
- Prepared statements to prevent SQL injection
- CSRF token protection on all forms
- Session-based authentication
- File upload validation for doctor certificates
- Role-based permission checks
- Output escaping to prevent XSS attacks

---

## Terms of Service & Privacy Policy
- Users must explicitly agree to the Terms of Service and Privacy Policy during registration
- Mandatory checkbox with frontend and backend validation
- Policy content displayed via modal pop-ups
- Sensitive health data is restricted to authorized roles only

---

## Database
- MySQL database
- Relational structure with foreign keys and constraints
- SQL file included in the repository for setup and testing

---

## Testing
- **PHPUnit Testing:** Not implemented

---

## Video Demonstration
🎥 **Demo Video Link:**  
Video Demo (YouTube): https://youtu.be/aPo04kJhzzQ

The video demonstrates:
- Application running on the live server
- Major system functionalities
- Selected backend logic
- Frontend validation
- Role-based workflows

---

## Repository Structure

This is the directory structure of the project, organized by functionality.

## **Root Structure**
- `.htaccess` – Apache rewrite rules and security headers.  
- `README.md` – Project documentation, setup instructions, and features.  
- `debug.php` – Debugging utilities (disable in production).  
- `index.php` – Main application entry point.

## **Directories**

### `api/`
Contains all backend API-related code.
- `controllers/` – Handles API endpoint logic.  
- `middleware/` – Authentication and validation middleware.  
- `routes/` – API route definitions.  
- `index.php` – API entry point.

### `app/`
Core application logic and configuration.
- `config/` – Configuration files.  
- `controllers/` – Main application controllers.  
- `models/` – Database models.  
- `libraries/` – Custom libraries and helper functions.  
- `core/` – Core framework files.

### `assets/`
Front-end static assets.
- `css/` – Stylesheets.  
- `js/` – JavaScript files.  
- `images/` – Image assets.  
- `fonts/` – Font files.

### `uploads/`
User-uploaded files and temporary storage.
- `profiles/` – Profile pictures.  
- `documents/` – Uploaded documents.  
- `certificates/` – Doctor certificates.  
- `temp/` – Temporary uploads.

### `views/`
Front-end templates and pages.
- `patient/` – Patient-facing pages.  
- `doctor/` – Doctor dashboard pages.  
- `admin/` – Admin panel pages.  
- `auth/` – Login and registration pages.  
- `partials/` – Reusable components (headers, footers, etc.).  
- `layouts/` – Base layout templates.

---
# Database Tables and Views

## **Tables**

1. `hc_users` – User accounts (patients, volunteers, doctors, admins)  
2. `hc_medical_requests` – Health consultation requests  
3. `hc_doctor_verifications` – Doctor certificate verification  
4. `hc_health_tips` – Health education articles  
5. `hc_tip_likes` – Likes on health tips  
6. `hc_training_resources` – Training materials for volunteers  
7. `hc_activity_logs` – System activity tracking  
8. `hc_user_sessions` – User login sessions  
9. `hc_feedback` – Patient feedback on responses  
10. `hc_forum_posts` – Community forum posts  
11. `hc_forum_comments` – Comments on forum posts  

## **Views** (not tables)

12. `hc_platform_stats` – Platform statistics view  
13. `hc_doctor_tips_stats` – Doctor tips statistics view  
14. `hc_training_stats` – Volunteer training statistics view

## Project Status
HealthConnect is a fully functional **Minimum Viable Product (MVP)** featuring:
- Secure authentication system
- Role-based dashboards
- Health request CRUD functionality
- Doctor verification workflow
- Admin approval system
- Responsive and modern UI
- Strong security foundation

The system is ready for academic evaluation and future expansion.

---

## Author
**Foureiratou ZAKARI YAOU IDI**  
Computer Science  
CS341 – Web Technologies
