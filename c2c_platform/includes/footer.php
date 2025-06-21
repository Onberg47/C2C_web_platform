</main>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> C2C Platform. All rights reserved.</p>
    </div>
</footer>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if (isset($pageSpecificJS)): ?>
    <script src="<?= BASE_URL ?>assets/js/<?= sanitizeInput($pageSpecificJS) ?>"></script>
<?php endif; ?>
</body>

</html>