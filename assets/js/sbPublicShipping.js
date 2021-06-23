SalesbeatPublicShipping = {
    params: {},

    init: function () {
        const shipping = document.querySelector('[data-shipping-init]');
        if (shipping)
            this.params = {
                name: shipping.dataset.name || '',
                token: shipping.dataset.token || '',
                city_code: shipping.dataset.cityCode || '',
                products: shipping.dataset.products || []
            };

        this.elementBlock = document.querySelector('#sb-cart-widget');
        this.elementResultBlock = document.querySelector('#sb-cart-widget-result');

        if (this.elementResultBlock && this.elementResultBlock.innerHTML) {
            this.addEventReshow(true);
        } else if (this.elementBlock && !this.elementBlock.innerHTML) {
            this.initShipping();
        }
    },
    initShipping: function (params) {
        if (!this.params) this.params = params;

        this.shippingBlock = this.elementBlock.closest('li');
        if (!this.shippingBlock) {
            console.log('Error shippingBlock: ', this.shippingBlock , '. Add attr li');
            return false;
        }

        this.shippingMethodInput = this.shippingBlock.querySelector('input');
        this.loadWidget();
    },
    loadWidget: function () {
        const me = this;

        SB.init_cart({
            token: this.params.token || '',
            city_code: this.params.city_code || '',
            products: this.params.products || [],
            callback: function (data) {
                data['action'] = 'sbCallBack';
                data['delivery_id'] = me.params.name;

                jQuery.post(sbAjaxOption.url, data)
                    .done(() => {
                        me.addEventReshow();
                        me.checkedMethodDelivery();
                    })
                    .fail((err) => {
                        console.log(err);
                    });
            }
        });

        this.clearResultBlock();
    },
    addEventReshow: function (init = false) {
        const me = this;

        let button = this.elementResultBlock.querySelector('.sb-reshow-cart-widget');
        if (!button) return false;

        button.addEventListener('click', (e) => {
            e.preventDefault();

            if (init) {
                me.initShipping();
            } else {
                me.reshowCardWidget();
            }

            this.clearResultBlock();
        });
    },
    reshowCardWidget: function () {
        SB.reinit_cart(true);
    },
    clearResultBlock: function () {
        this.elementResultBlock.innerHTML = '';
    },
    checkedMethodDelivery: function () {
        if (this.shippingMethodInput.type === 'hidden') {
            jQuery(document.body).trigger('wc_update_cart');
            jQuery(document.body).trigger('update_checkout');
        } else if (this.shippingMethodInput.type === 'radio') {
            this.shippingMethodInput.checked = false;
            this.shippingMethodInput.click();
        }
    }
};

document.addEventListener('DOMContentLoaded', (e) => {
    SalesbeatPublicShipping.init();

    const send = XMLHttpRequest.prototype.send
    XMLHttpRequest.prototype.send = function () {
        this.addEventListener('load', () => {
            const objUrl = SalesbeatTools.parseURL(this.responseURL);
            if (objUrl.search === '?wc-ajax=update_shipping_method' ||
                objUrl.search === '?wc-ajax=get_refreshed_fragments' ||
                objUrl.search === '?wc-ajax=update_order_review'
            )
                SalesbeatPublicShipping.init();
        });

        return send.apply(this, arguments);
    }
});