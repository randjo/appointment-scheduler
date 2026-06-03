function showPopup(message) {
	const toast = document.createElement('div');
	
	toast.className = `
            fixed bottom-5 right-5
            bg-green-600 text-white
            px-4 py-3 rounded shadow-lg
            transition-opacity duration-500
        `;
	
	toast.innerText = message;
	document.body.appendChild(toast);
	
	setTimeout(() => {
		toast.classList.add('opacity-0');
		toast.remove();
	}, 5000);
}

document.addEventListener('DOMContentLoaded', () => {
	const sessionMessageContainer = document.getElementById('flash-data');
	
	if (!sessionMessageContainer) return;
	
	showPopup(sessionMessageContainer.dataset.message, sessionMessageContainer.dataset.type);
});