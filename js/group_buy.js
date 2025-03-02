document.addEventListener('DOMContentLoaded', function() {
    const joinGroupBuyButtons = document.querySelectorAll('.join-group-buy');

    joinGroupBuyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const groupBuyId = this.getAttribute('data-group-buy-id');
            
            // Check if user is logged in (you'll need to implement session check)
            if (!isUserLoggedIn()) {
                showLoginPrompt();
                return;
            }

            // AJAX request to join group buy
            fetch('ajax/join_group_buy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_buy_id: groupBuyId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    updateGroupBuyProgress(groupBuyId, data.participants);
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Something went wrong. Please try again.');
            });
        });
    });

    function isUserLoggedIn() {
        // Implement user session check
        return true; // Placeholder
    }

    function showLoginPrompt() {
        // Redirect to login or show modal
        window.location.href = 'login.php?redirect=group_buy.php';
    }

    function showSuccessMessage(message) {
        const messageContainer = document.createElement('div');
        messageContainer.classList.add('alert', 'alert-success');
        messageContainer.textContent = message;
        document.body.appendChild(messageContainer);
        setTimeout(() => messageContainer.remove(), 3000);
    }

    function showErrorMessage(message) {
        const messageContainer = document.createElement('div');
        messageContainer.classList.add('alert', 'alert-danger');
        messageContainer.textContent = message;
        document.body.appendChild(messageContainer);
        setTimeout(() => messageContainer.remove(), 3000);
    }

    function updateGroupBuyProgress(groupBuyId, participants) {
        const dealCard = document.querySelector(`.join-group-buy[data-group-buy-id="${groupBuyId}"]`).closest('.deal-card');
        const progressBar = dealCard.querySelector('.progress-bar');
        const progressText = dealCard.querySelector('.progress-text');

        // Update progress bar and text
        const maxParticipants = parseInt(progressText.textContent.split('/')[1]);
        const newProgressPercentage = (participants / maxParticipants) * 100;
        
        progressBar.style.width = `${newProgressPercentage}%`;
        progressText.textContent = `${participants}/${maxParticipants} Joined`;
    }
});
