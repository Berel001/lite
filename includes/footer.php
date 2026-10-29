        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> Federal College of Wildlife Management</span>
            <span><?= ($user['role'] ?? '') === 'admin' ? 'Administration workspace' : (($user['role'] ?? '') === 'department' ? e($user['department_name'] ?? 'Department workspace') : 'Student complaint support') ?></span>
        </footer>
    </div>
</div>
<div class="sidebar-overlay" data-menu-toggle></div>
<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/libs/popper/popper.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script>
    document.querySelectorAll('[data-menu-toggle]').forEach(function (element) {
        element.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    });
</script>
</body>
</html>
