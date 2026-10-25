import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;
Alpine.start();

const scanner = document.getElementById('attendance-scanner');
if (scanner) import('./scanner').then(({ initializeScanner }) => initializeScanner(scanner));
if (document.getElementById('todayChart')) {
    import('chart.js/auto').then(module => {
        window.Chart = module.default;
        document.dispatchEvent(new Event('charts-ready'));
    });
}
