<div id="toast" class="toast">
    <span id="toast-message"></span>
    <button id="toast-close" onclick="hideToast()">×</button>
</div>
<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        toastMessage.textContent = message;
        toast.classList.add(type);
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.opacity = 1;
        }, 500);


        setTimeout(() => {
            hideToast();
        }, 3000);
    }

    function hideToast() {
        const toast = document.getElementById('toast');
        toast.style.opacity = 0;
        setTimeout(() => {
            toast.style.display = 'none';
        }, 500);
    }
    <?php if (isset($message)): ?>
        showToast("<?php echo $message; ?>", "<?php echo $type; ?>");
    <?php endif; ?>
</script>