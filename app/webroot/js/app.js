// Shared Alpine.js utilities and helpers

function debounce(fn, delay) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Expose debounce globally for use in Alpine x-data functions
window.debounce = debounce;
