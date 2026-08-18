# ✂️ Barbershop Appointment System

> A full-stack scheduling and management platform for barbershops, allowing customers to book services and barbers to manage their availability.

---

### 🚀 Key Features
* **Intelligent Booking Wizard:** A dynamic, multi-step booking process that filters available dates and time slots based on the selected barber's schedule and the service duration[cite: 16].
* **Role-Based Access Control (RBAC):** Secure authentication system with distinct privileges for Customers, Barbers, and Administrators[cite: 15].
* **Schedule Management:** Automated working hours mapping and schedule collision prevention to ensure time slots are accurately tracked[cite: 16, 18].
* **Appointment Tracking:** A dedicated dashboard for users to view, sort, and manage their upcoming and past appointments[cite: 14].

---

### 🛠️ Tech Stack & Architecture
* **Backend:** PHP with session-based authentication[cite: 14, 15, 16].
* **Database:** MySQL / MariaDB with structured foreign key constraints for data integrity.
* **Frontend:** HTML5, CSS3, JavaScript, and Bootstrap 5[cite: 14, 15, 16].
* **Package Management:** Composer[cite: 19, 20].
* **Dependencies:** `vlucas/phpdotenv` for secure environment variable management and `phpmailer/phpmailer` for email notifications[cite: 19].

---

### ⚙️ Quickstart (Local Development)

#### 1. Clone the Repository
```bash
git clone [https://github.com/hes0o/barbershop.git](https://github.com/hes0o/barbershop.git)
cd barbershop
