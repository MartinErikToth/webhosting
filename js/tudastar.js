document.getElementById('questionForm').addEventListener('submit', function() {
    setTimeout(function() {
        document.querySelector('.message').classList.add('show-message');
    }, 500);
});