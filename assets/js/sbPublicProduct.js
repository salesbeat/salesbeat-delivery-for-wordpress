SalesbeatPublicProduct = {
    params: {},

    init: function () {
        const shipping = document.querySelector('[data-shipping-delivery]');
        if (!shipping) return;

        this.variation = document.querySelector('input[name=variation_id]');

        this.params = {
            product_id: shipping.dataset.productId || 0,
            token: shipping.dataset.token || '',
            city_by: shipping.dataset.cityBy || '',
            params_by: shipping.dataset.paramsBy || '',
            main_div_id: shipping.dataset.mainDivId || '',
        };

        if (this.variation) this.bind();
        this.initProduct();
    },
    bind: function () {
        SalesbeatTools.trackChange(this.variation);

        this.variation.addEventListener('change', () => {
            SalesbeatPublicProduct.initProduct();
        });
    },
    getProduct: function () {
        let data = {
            'action': 'sbGetProductInfo',
            'product_id': this.params.product_id,
            'quantity': 1,
        };

        if (this.variation && this.variation.value) data['variation_id'] = this.variation.value;

        return jQuery.post(sbAjaxOption.url, data);
    },
    initProduct: function () {
        this.getProduct()
            .done((res) => {
                this.loadWidget(res);
            })
            .fail((err) => {
                console.log(err);
            });
    },
    loadWidget: function (product) {
        SB.init({
            token: this.params.token,
            price_to_pay: product.price_to_pay,
            price_insurance: product.price_insurance,
            weight: product.weight,
            x: product.x,
            y: product.y,
            z: product.z,
            quantity: product.quantity,
            city_by: this.params.city_by,
            params_by: this.params.params_by,
            main_div_id: this.params.main_div_id,
            callback: function () {
                console.log('Salesbeat is ready!');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', (e) => {
    SalesbeatPublicProduct.init();
});