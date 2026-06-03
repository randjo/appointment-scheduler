window.confirmDelete = function (id) {
	Swal.fire({
		title: 'Are you sure?',
		text: "Are you sure you want to delete this appointment?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Yes, delete it!',
		cancelButtonText: 'No'
	}).then((result) => {
		if (result.isConfirmed) {
			fetch(`/appointments/${id}`, {
				method: 'DELETE',
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
					'Content-Type': 'application/json'
				}
			}).then(() => {
				window.location.reload();
			});
		}
	});
};