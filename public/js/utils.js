window.copyText = function (id, label) {
    let text = document.getElementById(id).innerText.trim();

    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Đã copy ' + label,
            text: text,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
    });
};

window.normalizeName = function (str) {
    return str.normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d").replace(/Đ/g, "D")
        .toUpperCase()
        .trim();
};
