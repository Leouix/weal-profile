/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-user-account
 */

let formUserButton
let successNotice
let lockUrlIcon
let urlInput
let dashiconsUnlock
let dashiconsLock
window.addEventListener(
    'load',
    function () {

        formUserButton = document.getElementById('save-create-button');
        successNotice = document.getElementById('success-notice');
        lockUrlIcon = document.getElementById('lock-url');
        urlInput = document.getElementById('adu-form-input');
        dashiconsUnlock = document.getElementById('dashicons-unlock');
        dashiconsLock = document.getElementById('dashicons-lock');


        lockUrlIcon?.addEventListener(
            'click',
            function () {
                urlInput.disabled = !urlInput.disabled;

                if (urlInput.disabled) {
                    dashiconsLock.classList.remove('hidden');
                    dashiconsLock.classList.add('visible');

                    dashiconsUnlock.classList.remove('visible');
                    dashiconsUnlock.classList.add('hidden');
                } else {
                    dashiconsLock.classList.remove('visible');
                    dashiconsLock.classList.add('hidden');

                    dashiconsUnlock.classList.remove('hidden');
                    dashiconsUnlock.classList.add('visible');
                }
            }
        );


        function saveMyAccountSettingsForm(elForm) {
            var formData = new FormData(elForm);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', myAccountAdminData.root + 'my-account/v1/admin-save-page-settings/', true);
            xhr.setRequestHeader('X-WP-Nonce', myAccountAdminData.nonce);
            xhr.onreadystatechange = function (res) {
                if (4 === this.readyState && 200 === this.status) {
                    successAjaxButtonEvent('success');
                    lockLinkField();
                }
                if (4 === this.readyState && (404 === this.status || 401 === this.status)) {
                    console.log('An error occurred');
                }
            };
            xhr.send(formData);
        }

        function lockLinkField() {
            urlInput.disabled = true;
            dashiconsLock.classList.remove('hidden');
            dashiconsLock.classList.add('visible');
            dashiconsUnlock.classList.remove('visible');
            dashiconsUnlock.classList.add('hidden');
        }

        function successAjaxButtonEvent(statusClass) {
            formUserButton.classList.add(statusClass);
            successNotice.style.display = 'block';
            if ('success' === statusClass) {
                setTimeout(
                    function () {
                        formUserButton.classList.remove(statusClass);
                        successNotice.style.display = 'none';
                    },
                    1000
                );
            }
        }


        var form = document.getElementById('admin-user-account-form');
        form.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();
                saveMyAccountSettingsForm(event.target);
            }
        );
    }
);
