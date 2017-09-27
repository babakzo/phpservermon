$(function() {
    setTimeout(function () {
        try {
            var d = document.getElementById('user_login');
            d.focus();
            d.select();
        } catch (e) {}
    }, 200);
});