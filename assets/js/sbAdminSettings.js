SalesbeatAdminSettings = {
    init: function (params) {
        this.params = params;

        this.actionTab();
        this.actionSync();
    },
    actionTab: function () {
        const dataTabs = document.querySelectorAll('[data-tab]');
        for (let dataTab of dataTabs) {
            dataTab.addEventListener('click', (e) => {
                e.preventDefault();

                const tabsName = dataTab.getAttribute('data-tab');
                const id = dataTab.getAttribute('data-tab-id');

                const tabs = document.querySelectorAll('[data-tab=' + tabsName + ']');
                for (let tab of tabs) tab.classList.remove('nav-tab-active');

                let tabId = document.querySelector('[data-tab-id=' + id + ']');
                tabId.classList.add('nav-tab-active');

                const contents = document.querySelectorAll('[data-tab-content=' + tabsName + ']');
                for (let content of contents) content.classList.remove('is-active');

                let contentId = document.querySelector('[data-tab-content-id=' + id + ']');
                contentId.classList.add('is-active');
            });
        }
    },
    actionSync: function () {
        const me = this;

        let elementSync = document.querySelector('[data-action-sync]');
        elementSync.addEventListener('click', (e) => {
            e.preventDefault();

            jQuery.post(sbAjaxOption.url, {action: 'sbSyncPaySystem'})
                .done((res) => {
                    me.result(res);
                })
                .fail((err) => {
                    console.log(err);
                });
        });
    },
    result: function(result) {
        alert(result.message);
    }
};