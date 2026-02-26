<?php
// FILENAME: employee/template/footer.php
$company_name = $_SESSION['settings']['company_name'] ?? 'Employee Portal';
$year = date('Y');
?>
</main>
    <footer class="py-2 text-center text-xs text-gray-600 bg-white border-t-2 border-indigo-500 shadow-[0_-2px_4px_rgba(0,0,0,0.05)] z-50">
        <span class="mr-1">&copy; <?php echo $year; ?> <strong><?php echo htmlspecialchars($company_name); ?></strong>.</span>
        <span class="hidden sm:inline">All rights reserved.</span>
        <span class="mx-2 text-gray-300">|</span>
        <span>Developed by <span class="font-semibold text-indigo-600">Ymath</span></span>
    </footer>
</div> </div> </body>
</html>
