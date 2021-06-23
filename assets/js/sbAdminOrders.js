SalesbeatAdminOrders = {
    init: function (params) {
        this.params = params;

        this.actionSendOrder();
    },
    actionSendOrder: function () {
        const me = this;

        const buttons = document.querySelectorAll('[data-send-order]');
        for (let button of buttons) {
            button.addEventListener('click', (e) => {
                e.preventDefault();

                const orderId = button.getAttribute('data-order-id');
                jQuery.post(sbAjaxOption.url, {action: 'sbSendOrder', order_id: orderId})
                    .done((res) => {
                        me.result(res);
                    })
                    .fail((err) => {
                        console.log(err);
                    });
            });
        }
    },
    result: function (result) {
        if (result.status === 'success') {
            const element = document.querySelector('[data-order-id="' + result.data.order_id + '"]');
            const order = element.closest('tr');
            let trackCode = order.querySelector('.ex_sb_tracking_number');
            trackCode.innerHTML = result.data.track_code;

            alert(result.message);
        } else {
            let message = 'Ошибка:\n';
            if (result.error_list) {
                const count = result.error_list.length;
                for (let err of result.error_list)
                    message += err.message + '\n'
            } else {
                message += 'Нет информации об ошибке'
            }

            alert(message);
        }
    }
};