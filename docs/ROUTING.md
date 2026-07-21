# Routing

- `web.php` owns public/shared web routes and loads `auth.php`.
- `api.php` exposes throttled routes beneath `/api/v1`.
- `student.php`, `adviser.php`, `panelist.php`, `facilitator.php`, `dean.php`, and `admin.php` are registered in `bootstrap/app.php`.

Role routes combine `auth`, `verified`, `active`, Spatie `role`, and `permission` middleware. Names use the matching prefix (`student.dashboard`, `admin.dashboard`, and so on). Record-level authorization still belongs in policies; route middleware alone cannot establish ownership or assignment.
