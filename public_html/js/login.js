let currentToken = null;

window.loginTele = function () {
    document.getElementById('loading').style.display = 'block';

    fetch('/login/telegram')
        .then(res => res.json())
        .then(data => {
            if (data.link) {
                window.location.href = data.link;
                currentToken = data.token;
            } else {
                alert(data.error);
            }
        });
};

setInterval(() => {
    if (!currentToken) return;

    fetch('/check-login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            token: currentToken
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.logged_in) {
                window.location.href = '/';
            }
        });

}, 3000);
