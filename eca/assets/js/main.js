$(function () {
    $('.datatable').DataTable({
        responsive: true,
        pageLength: 10
    });

    $('.confirm-delete').on('click', function (event) {
        event.preventDefault();
        const href = $(this).attr('href');

        Swal.fire({
            icon: 'warning',
            title: 'Delete this record?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#202959',
            cancelButtonColor: '#69708a'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});
