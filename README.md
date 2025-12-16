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

> ⚠️ The server will remain active until the end of January 2026 as required.

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
(Will be added / Provided in the project PDF submission)

The video demonstrates:
- Application running on the live server
- Major system functionalities
- Selected backend logic
- Frontend validation
- Role-based workflows

---

## Repository Structure

---

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
