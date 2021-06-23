SalesbeatTools = {
    requestWrapper(url, options) {
        return new Promise((resolve, reject) => {
            let xhr = new XMLHttpRequest();
            xhr.open(options.method || 'GET', url);
            xhr.onreadystatechange = function () {
                if (xhr.status !== 200)
                    reject('Ошибка сервера: ' + this.status);
            };
            xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
            xhr.onload = () => {
                resolve('response' in xhr ? xhr.response : xhr.responseText);
            };
            xhr.onerror = (xhr, err) => {
                reject(err);
            };
            xhr.send(JSON.stringify(options.data));
        });
    },
    parseURL(url) {
        let parser = document.createElement('a');
        let searchObject = {};

        parser.href = url;

        let queries = parser.search.replace(/^\?/, '').split('&');
        for (let i = 0; i < queries.length; i++) {
            const split = queries[i].split('=');
            searchObject[split[0]] = split[1];
        }

        return {
            protocol: parser.protocol,
            host: parser.host,
            hostname: parser.hostname,
            port: parser.port,
            pathname: parser.pathname,
            search: parser.search,
            searchObject: searchObject,
            hash: parser.hash
        };
    },
    trackChange: function(element) {
        MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

        let observer = new MutationObserver((mutations, observer) => {
            if (mutations[0].attributeName === 'value')
                element.dispatchEvent(new Event('change'));
        });
        observer.observe(element, {
            attributes: true
        });
    }
};