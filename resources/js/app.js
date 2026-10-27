import Chart from 'chart.js/auto';

window.Chart = Chart;

// wire:navigate swaps the <head> icon link, but browsers keep showing the old
// tab icon until the element is re-inserted, so re-add it after each visit.
document.addEventListener('livewire:navigated', () => {
    const icon = document.querySelector('link[rel="icon"][type="image/svg+xml"]');

    if (icon) {
        const fresh = icon.cloneNode();

        icon.replaceWith(fresh);
    }
});
