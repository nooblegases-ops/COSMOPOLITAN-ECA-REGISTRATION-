$(function () {
    let clickAudioContext;

    const playClickSound = function () {
        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        clickAudioContext = clickAudioContext || new AudioContext();

        if (clickAudioContext.state === 'suspended') {
            clickAudioContext.resume();
        }

        const oscillator = clickAudioContext.createOscillator();
        const gain = clickAudioContext.createGain();
        const startTime = clickAudioContext.currentTime;

        oscillator.type = 'triangle';
        oscillator.frequency.setValueAtTime(680, startTime);
        oscillator.frequency.exponentialRampToValueAtTime(420, startTime + 0.045);

        gain.gain.setValueAtTime(0.0001, startTime);
        gain.gain.exponentialRampToValueAtTime(0.12, startTime + 0.008);
        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.07);

        oscillator.connect(gain);
        gain.connect(clickAudioContext.destination);
        oscillator.start(startTime);
        oscillator.stop(startTime + 0.075);
    };

    $(document).on('click', 'a, button, input[type="button"], input[type="submit"], input[type="reset"], [role="button"], .btn, .nav-link, .file-upload, .choice-pill, .icon-btn', function () {
        if ($(this).is(':disabled, [aria-disabled="true"]')) {
            return;
        }

        playClickSound();
    });

    $('.datatable').DataTable({
        responsive: true,
        pageLength: 10
    });

    $('.confirm-delete').on('click', function (event) {
        event.preventDefault();
        const href = $(this).attr('href');

        Swal.fire({
            icon: 'warning',
            title: 'Delete this record?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#202959',
            cancelButtonColor: '#69708a'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    const photoInput = document.getElementById('photo');
    const cropModal = document.getElementById('photoCropModal');
    const cropCanvas = document.getElementById('cropCanvas');
    const cropZoom = document.getElementById('cropZoom');
    const croppedPhotoData = document.getElementById('cropped_photo_data');
    const applyCrop = document.getElementById('applyCrop');
    const cancelCrop = document.getElementById('cancelCrop');

    if (photoInput && cropModal && cropCanvas && cropZoom && croppedPhotoData && applyCrop && cancelCrop) {
        const context = cropCanvas.getContext('2d');
        const cropImage = new Image();
        let imageReady = false;
        let offsetX = 0;
        let offsetY = 0;
        let isDragging = false;
        let lastX = 0;
        let lastY = 0;

        const drawCrop = function () {
            if (!imageReady) {
                return;
            }

            const zoom = Number(cropZoom.value);
            const baseScale = Math.max(cropCanvas.width / cropImage.width, cropCanvas.height / cropImage.height);
            const scale = baseScale * zoom;
            const drawWidth = cropImage.width * scale;
            const drawHeight = cropImage.height * scale;
            const x = (cropCanvas.width - drawWidth) / 2 + offsetX;
            const y = (cropCanvas.height - drawHeight) / 2 + offsetY;

            context.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
            context.fillStyle = '#eef0f6';
            context.fillRect(0, 0, cropCanvas.width, cropCanvas.height);
            context.drawImage(cropImage, x, y, drawWidth, drawHeight);
        };

        const openCropModal = function () {
            cropModal.classList.add('active');
            cropModal.setAttribute('aria-hidden', 'false');
        };

        const closeCropModal = function () {
            cropModal.classList.remove('active');
            cropModal.setAttribute('aria-hidden', 'true');
        };

        photoInput.addEventListener('change', function () {
            const file = photoInput.files && photoInput.files[0];

            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                cropImage.onload = function () {
                    imageReady = true;
                    offsetX = 0;
                    offsetY = 0;
                    cropZoom.value = '1';
                    drawCrop();
                    openCropModal();
                };
                cropImage.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });

        cropZoom.addEventListener('input', drawCrop);

        cropCanvas.addEventListener('pointerdown', function (event) {
            isDragging = true;
            lastX = event.clientX;
            lastY = event.clientY;
            cropCanvas.setPointerCapture(event.pointerId);
        });

        cropCanvas.addEventListener('pointermove', function (event) {
            if (!isDragging) {
                return;
            }

            offsetX += event.clientX - lastX;
            offsetY += event.clientY - lastY;
            lastX = event.clientX;
            lastY = event.clientY;
            drawCrop();
        });

        cropCanvas.addEventListener('pointerup', function () {
            isDragging = false;
        });

        cropCanvas.addEventListener('pointercancel', function () {
            isDragging = false;
        });

        applyCrop.addEventListener('click', function () {
            if (!imageReady) {
                return;
            }

            croppedPhotoData.value = cropCanvas.toDataURL('image/png');
            const preview = document.querySelector('.photo-preview');

            if (preview) {
                preview.innerHTML = '';
                const image = document.createElement('img');
                image.src = croppedPhotoData.value;
                image.alt = 'Selected member picture';
                preview.appendChild(image);
            }

            photoInput.value = '';
            closeCropModal();
        });

        cancelCrop.addEventListener('click', function () {
            photoInput.value = '';
            closeCropModal();
        });
    }

    const intakeModal = document.getElementById('intakeModal');
    const openIntakeModal = document.getElementById('openIntakeModal');
    const closeIntakeModal = document.getElementById('closeIntakeModal');

    if (intakeModal && openIntakeModal && closeIntakeModal) {
        openIntakeModal.addEventListener('click', function () {
            intakeModal.classList.add('active');
            intakeModal.setAttribute('aria-hidden', 'false');
        });

        closeIntakeModal.addEventListener('click', function () {
            intakeModal.classList.remove('active');
            intakeModal.setAttribute('aria-hidden', 'true');
        });
    }
});
