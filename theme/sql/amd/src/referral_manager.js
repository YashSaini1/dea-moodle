define('theme_sql/referral_manager', ['jquery'], function($) {
    return {
        init: (args) => {
            console.log(args);
            $(document).ready(() => {
                const referralBlock = document.querySelector('.referral'),
                    bodyEl = document.body;

                if (referralBlock) {
                    // Copy Button Script
                    let copyButton = referralBlock.querySelector('button'),
                        inputField = referralBlock.querySelector('input');

                    copyButton.addEventListener('click', () => {
                        inputField.select();
                        inputField.setSelectionRange(0, 99999);

                        document.execCommand('copy');

                        copyButton.textContent = 'Copied!';
                        setTimeout(() => {
                            copyButton.textContent = 'Copy';
                        }, 1500);
                    });

                    // Pop-up Script
                    let popButtons = document.querySelectorAll('.pop-up-btn'),
                        popBlock = document.querySelector('.withdraw'),
                        darkBg = document.querySelector('.dark-bg');

                    popButtons.forEach((button) => {
                        button.addEventListener("click", () => openPopup());
                    });


                    function openPopup() {
                        popBlock.classList.toggle('open');
                        bodyEl.classList.toggle('open');
                        darkBg.classList.toggle('show');

                        document.addEventListener('keyup', handleEscape);
                        document.addEventListener('click', handleOutsideClick);
                    }

                    function handleEscape(event) {
                        if (event.code === 'Escape') {
                            closePopup();
                            removeListeners();
                        }
                    }

                    function handleOutsideClick(event) {
                        let closeButton = document.querySelector('.withdraw__wrapper_close-button'),
                            openButton = referralBlock.querySelector('.pop-up-btn');

                        if (!openButton.contains(event.target) &&
                            !closeButton.contains(event.target) &&
                            !popBlock.contains(event.target)) {
                            closePopup();
                            removeListeners();
                        }
                    }

                    function closePopup() {
                        popBlock.classList.remove('open');
                        bodyEl.classList.remove('open');
                        darkBg.classList.remove('show');
                    }

                    function removeListeners() {
                        document.removeEventListener('keyup', handleEscape);
                        document.removeEventListener('click', handleOutsideClick);
                    }

                    // Select All Button Script
                    let copyAllButton = document.querySelector('.selectAll'),
                        balanceElement = document.querySelector('.balance'),
                        amountInput = document.querySelector('#amount');

                    if (copyAllButton) {
                        copyAllButton.addEventListener('click', () => {
                            amountInput.value = balanceElement.textContent.replace(/[^0-9.]/g, '');
                        });
                    }
                }
                const withdraw_block = document.querySelector('.withdraw');

                if (withdraw_block) {
                    let emailInput = withdraw_block.querySelector('.withdraw__top_email input'),
                        block_payment = withdraw_block.querySelector('.withdraw__top_balance_payment'),
                        amountInput = withdraw_block.querySelector('.withdraw__top_balance_payment input'),
                        sendPaymentButton = withdraw_block.querySelector('.withdraw__top_button'),
                        paymentStatus = withdraw_block.querySelector('.message'),
                        balanceElement = withdraw_block.querySelector('.balance'),
                        balance = parseFloat(balanceElement.textContent),
                        sesskey = args.sesskey,
                        checkbox = document.getElementById('confirmationCheckbox'),
                        button = document.getElementById('sendPaymentButton');

                    function fetchPaymentHistory() {
                        let url = '/local/sql/paypal_history.php?sesskey=' + sesskey;
                        let xhr = new XMLHttpRequest();
                        xhr.open('GET', url, true);

                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                let response = JSON.parse(xhr.responseText);
                                if (response.status) {
                                    updatePaymentHistory(response.history);
                                } else {
                                    paymentStatus.innerHTML = 'Failed to download the history:' + response.message;
                                    error_styles();
                                    spinner_out();
                                    updatePaymentHistory(response.history);
                                }
                            }
                        };

                        xhr.onerror = function () {
                            paymentStatus.innerHTML = 'Error during data loading';
                            error_styles();
                            spinner_out();
                        };

                        xhr.send();
                    }

                    fetchPaymentHistory();

                    function updatePaymentHistory(history) {
                        let tbody = withdraw_block.querySelector('.withdraw__history_info_table').getElementsByTagName('tbody')[0];
                        tbody.innerHTML = '';

                        history.forEach(function (payment) {
                            let confirm = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none"\n' + ' xmlns="http://www.w3.org/2000/svg">\n' + ' <rect width="32" height="32" rx="16" fill="#E0FADE"/>\n' + ' <path d="M11.4294 15.3981L14.8822 18.8126L20.5705 13.1874" stroke="#3EDE33"\n' + ' stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>\n' + '</svg>',
                                failed = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">\n' + '<rect width="32" height="32" rx="16" fill="#FEF0F0"/>\n' + '<path d="M20.5 11.5L11.5 20.5" stroke="#F03D3E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>\n' + '<path d="M20.5 20.5L11.5 11.5" stroke="#F03D3E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>\n' + '</svg>',
                                waiting = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">\n' + '<rect width="32" height="32" rx="16" fill="#FFF3D7"/>\n' + '<path fill-rule="evenodd" clip-rule="evenodd" d="M16 10.3335C12.8753 10.3335 10.3333 12.8755 10.3333 16.0002C10.3333 19.1248 12.8753 21.6668 16 21.6668C19.1247 21.6668 21.6667 19.1248 21.6667 16.0002C21.6667 12.8755 19.1247 10.3335 16 10.3335ZM16 22.6668C12.324 22.6668 9.33334 19.6762 9.33334 16.0002C9.33334 12.3242 12.324 9.3335 16 9.3335C19.676 9.3335 22.6667 12.3242 22.6667 16.0002C22.6667 19.6762 19.676 22.6668 16 22.6668Z" fill="#FEB50D"/>\n' + '<path fill-rule="evenodd" clip-rule="evenodd" d="M18.2874 18.4618C18.2001 18.4618 18.1121 18.4391 18.0314 18.3918L15.5181 16.8925C15.3674 16.8018 15.2741 16.6385 15.2741 16.4625V13.2305C15.2741 12.9545 15.4981 12.7305 15.7741 12.7305C16.0508 12.7305 16.2741 12.9545 16.2741 13.2305V16.1785L18.5441 17.5318C18.7808 17.6738 18.8588 17.9805 18.7174 18.2178C18.6234 18.3745 18.4574 18.4618 18.2874 18.4618Z" fill="#FEB50D"/>\n' + '</svg>';

                            let status = '',
                                amountFormatted = '$' + parseFloat(payment.amount).toFixed(2),
                                formattedDate = payment.data,
                                email = payment.email;

                            if (payment.success === "0") {
                                status = failed;
                            } else if (payment.success === "1") {
                                status = confirm;
                            } else if (payment.success === "2") {
                                status = waiting;
                            }

                            let row = tbody.insertRow();
                            row.innerHTML = `
                    <td>${payment.id}</td>
                    <td>${email}</td>
                    <td>${amountFormatted}</td>
                    <td>${formattedDate}</td>
                    <td>${status}</td>
                `;
                        });
                    }


                    sendPaymentButton.addEventListener('click', function () {
                        let email = emailInput.value,
                            amount = parseFloat(amountInput.value);

                        if (amount < 0 || !amount || isNaN(amount)) {
                            amountInput.value = '';
                            paymentStatus.innerHTML = 'Please enter valid amount';
                            error_styles();
                            spinner_out();
                            return;
                        }

                        let email_pattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!email || !email_pattern.test(email)) {
                            error_styles();
                            spinner_out();
                            paymentStatus.innerHTML = 'Please enter a valid email address.';
                            return;
                        }

                        if (amount > balance) {
                            error_styles();
                            spinner_out();
                            paymentStatus.innerHTML = 'Amount exceeds your current balance!';
                            return;
                        }

                        let loading_spinner = withdraw_block.querySelector('.loading-spinner');
                        loading_spinner.style.display = 'block';
                        paymentStatus.style.display = 'none';
                        block_payment.classList.add('spinner');
                        sendPayment(email, amount);
                    });

                    function sendPayment(email, amount) {
                        let xhr = new XMLHttpRequest();
                        xhr.open('POST', '/local/sql/paypal_processing.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                        xhr.onload = function () {
                            let response = JSON.parse(xhr.responseText);
                            if (xhr.status === 200) {
                                spinner_out();
                                if (response.status) {
                                    paymentStatus.innerHTML = response.message;
                                    success_styles();
                                } else {
                                    paymentStatus.innerHTML = 'Error: ' + response.message;
                                    error_styles();
                                }
                            } else {
                                paymentStatus.innerHTML = 'Payment failed. ' + response.message;
                                error_styles();
                                spinner_out();
                            }
                            fetchPaymentHistory();
                        };

                        xhr.onerror = function () {
                            paymentStatus.innerHTML = 'Payment failed. Server error.';
                            error_styles();
                            spinner_out();
                        };

                        xhr.send('email=' + encodeURIComponent(email) +
                            '&amount=' + encodeURIComponent(amount) +
                            '&sesskey=' + encodeURIComponent(sesskey));
                        checkbox.checked = false;
                        button.disabled = true;
                    }


                    function success_styles() {
                        if (paymentStatus.classList.contains('error')) {
                            paymentStatus.classList.remove('error');
                            block_payment.classList.remove('error');

                            block_payment.classList.add('success');
                            paymentStatus.classList.add('success');
                        } else {
                            block_payment.classList.add('success');
                            paymentStatus.classList.add('success');
                        }
                    }

                    function error_styles() {
                        if (paymentStatus.classList.contains('success')) {
                            paymentStatus.classList.remove('success');
                            block_payment.classList.remove('success');

                            block_payment.classList.add('error');
                            paymentStatus.classList.add('error');
                        } else {
                            block_payment.classList.add('error');
                            paymentStatus.classList.add('error');
                        }
                    }

                    function spinner_out() {
                        let loading_spinner = withdraw_block.querySelector('.loading-spinner');
                        loading_spinner.style.display = 'none';
                        paymentStatus.style.display = 'block';
                        block_payment.classList.remove('spinner');
                    }
                    checkbox.addEventListener('change', () => {
                        button.disabled = !checkbox.checked;
                    });
                }
            });
        }
    };
});
