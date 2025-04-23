import QRCode from 'qrcode';

document.addEventListener('DOMContentLoaded', () => {
    const qrCanvas = document.getElementById('qr-canvas');

    if (qrCanvas) {
        const url = qrCanvas.dataset.url;

        QRCode.toCanvas(qrCanvas, url, {
            width: 130,
            margin: 1,
            color: {
                dark: '#000',
                light: '#ffffff'
            }
        }, function (error) {
            if (error) console.error(error);
        });
    }
});
