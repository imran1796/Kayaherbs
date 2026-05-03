<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
<script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000,
    };

    window.adminToast = function (message, type = 'success') {
        const toastType = type === 'danger' ? 'error' : type;
        toastr[toastType] ? toastr[toastType](message) : toastr.info(message);
    };
</script>
