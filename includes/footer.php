</main>

<footer class=" bg-dark py-2 text-light mt-auto footer-modern">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">

            <p class="mb-2 mb-md-0">
                &copy; <span id="year"></span> <?php echo APP_NAME ?? 'Ahilya Typing'; ?>
            </p>

            <div class="text-center text-md-end">
                <div>
                    <a href="<?php echo BASE_URL; ?>privacy.php" class="me-3">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>terms.php" class="me-3">Terms</a>
                    <a href="<?php echo BASE_URL; ?>support.php">Support</a>
                </div>
                <small class="d-block mt-1 text-light">
                    Developed by Perfect Software
                </small>
            </div>

        </div>

    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Year Script -->
<script>
document.getElementById("year").textContent = new Date().getFullYear();
</script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

</body>
</html>
