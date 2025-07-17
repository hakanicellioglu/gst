(function () {
    const BREAKPOINT = 768; // matches CSS @media breakpoint

    function expectedView() {
        return window.innerWidth < BREAKPOINT ? 'card' : 'list';
    }

    function currentView() {
        const params = new URLSearchParams(window.location.search);
        return params.get('view');
    }

    function updateView() {
        const params = new URLSearchParams(window.location.search);
        const want = expectedView();
        const current = params.get('view');
        if (current !== want) {
            params.set('view', want);
            const query = params.toString();
            const url = window.location.pathname + (query ? '?' + query : '');
            window.location.replace(url);
        }
    }

    window.addEventListener('resize', updateView);
    document.addEventListener('DOMContentLoaded', updateView);
})();
