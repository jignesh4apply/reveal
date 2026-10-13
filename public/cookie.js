document.addEventListener("DOMContentLoaded", function() {
    if (!document.cookie.includes('cookiesAccepted=true') && !document.cookie.includes('cookiesCanceled=true')) {
        document.getElementById('cookieConsentPopup').style.display = 'block';
    }

    document.getElementById('acceptCookies').addEventListener('click', function() {
        const expirationDate = new Date();
        expirationDate.setFullYear(expirationDate.getFullYear() + 20);
        document.cookie = `cookiesAccepted=true; expires=${expirationDate.toUTCString()}; path=/`;
        document.getElementById('cookieConsentPopup').style.display = 'none';
    });

    document.getElementById('cancelCookies').addEventListener('click', function() {
        document.cookie = "cookiesCanceled=true; path=/";
        document.getElementById('cookieConsentPopup').style.display = 'none';
    });
});