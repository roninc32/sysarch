document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(event) {
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!username || !email || !password) {
            alert('Please fill in all fields.');
            event.preventDefault();
        }
    });

    // Function to toggle the edit profile modal visibility
    window.toggleEditProfile = function() {
        var modal = document.getElementById('editProfileModal');
        modal.classList.toggle('hidden');  // Toggle the visibility by adding/removing 'hidden' class
    }

    // Close the modal if clicked outside of it
    window.addEventListener("click", function (event) {
        var modal = document.getElementById('editProfileModal');
        if (modal && !modal.contains(event.target) && event.target !== document.querySelector('.bg-indigo-500')) {
            modal.classList.add('hidden');  // Close modal if clicked outside
        }
    });

    // Make the success message slowly disappear after 3 seconds
    setTimeout(function() {
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.classList.add('fade-out');
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 1000); // Match the duration of the fade-out transition
        }
    }, 3000);

    // Search functionality for reservation history
    document.getElementById('search').addEventListener('input', function() {
        var searchValue = this.value.toLowerCase();
        var rows = document.querySelectorAll('#reservationTable tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            var match = false;
            cells.forEach(function(cell) {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    match = true;
                }
            });
            row.style.display = match ? '' : 'none';
        });
    });

    // Toggle mobile menu
    document.getElementById("mobile-menu-button").addEventListener("click", function () {
        var mobileMenu = document.getElementById("mobile-menu");
        mobileMenu.classList.toggle("hidden");
    });
});
