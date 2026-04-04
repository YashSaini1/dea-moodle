define([], function () {
    let highlight_code = (elements) => {
        elements.forEach(e => window.Prism.highlightElement(e));
    };

    return {
        init: (prismjs_url) => {
            let elements = document.querySelectorAll('code[class*=language-],pre[class*=language-]');
            if (elements.length < 1){
                return;
            }

            if (window.Prism){
                highlight_code(elements);
                return;
            }
            const script = document.createElement('script');
            script.src = prismjs_url;
            script.async = true;

            script.onload = () => {
                highlight_code(elements);
            };
            script.onerror = () => {
                console.log(error);
            };
            document.body.appendChild(script);
        },
    };
});
