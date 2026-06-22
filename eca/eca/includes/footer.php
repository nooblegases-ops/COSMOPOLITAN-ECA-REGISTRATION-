        </section>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
<?php if (!empty($_GET['alert_message'])): ?>
    <script>
        Swal.fire({
            icon: <?= json_encode($_GET['alert_type'] ?? 'success') ?>,
            title: <?= json_encode($_GET['alert_message']) ?>,
            confirmButtonColor: '#202959'
        });
    </script>
<?php endif; ?>
</body>
</html>
