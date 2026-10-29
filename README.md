# FCWM Student Complaint Support

A PHP and MySQL student support portal built to run from the existing XAMPP project folder and reuse its bundled Bootstrap/material dashboard assets.

## Run with XAMPP

1. Copy the project into `C:\xampp\htdocs\lite` (or use the current folder).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/lite/`.
4. On the first request, the application creates the `fcwm_student_support` database, its tables, the initial school and departments, and an administrator account.

The default database connection is `127.0.0.1:3306`, database `fcwm_student_support`, user `root`, and a blank password, matching a standard local XAMPP install. Set the optional `FCWM_DB_HOST`, `FCWM_DB_PORT`, `FCWM_DB_NAME`, `FCWM_DB_USER`, and `FCWM_DB_PASSWORD` environment variables to use different MySQL settings. The MySQL account needs permission to create the database and tables.

## Initial administrator

- Email: `admin@fcwm.edu.ng`
- Temporary password: `Admin@123`

Change this password at the first sign-in. Department staff accounts are created by the administrator and must also change their temporary password before continuing.

## Main workflows

- Students register with matric number, name, email, school, department, and password.
- Students submit complaints under a topic, receive a unique `FCWM-YEAR-...` reference, and view progress in their account.
- The public tracking form requires both the reference number and the student's matric number.
- Administrators route complaints to a department and can manage schools, departments, and department staff accounts.
- Department staff can only open complaints assigned to their own department, then post a response and update status.

## Notes

The initial school and department names are starter data. Administrators can add schools and departments to match the college's current structure. The database is initialized automatically, but MySQL must be running before the application can load.
