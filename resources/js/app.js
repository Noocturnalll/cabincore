

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Import SweetAlert2 globally
import Swal from 'sweetalert2';
window.Swal = Swal;

// Import ApexCharts globally
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

// Import Flatpickr
import flatpickr from 'flatpickr';
window.flatpickr = flatpickr;

// Import FilePond
import * as FilePond from 'filepond';
window.FilePond = FilePond;

// Import PhotoSwipe
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import PhotoSwipe from 'photoswipe';
window.PhotoSwipeLightbox = PhotoSwipeLightbox;
window.PhotoSwipe = PhotoSwipe;
