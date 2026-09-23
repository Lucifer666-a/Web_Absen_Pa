document.addEventListener("DOMContentLoaded", function () {
    var canvas = document.getElementById('signature-pad');
    var clearButton = document.getElementById('clear-signature');
    var signatureInput = document.getElementById('signature64');
    var form = document.getElementById('absen-form');

    if (canvas) {
        // Adjust canvas resolution for high-DPI screens
        function resizeCanvas() {
            var ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }

        window.onresize = resizeCanvas;
        resizeCanvas();

        // Initialize Signature Pad
        var signaturePad = new SignaturePad(canvas, {
            penColor: 'rgb(0, 0, 0)',
            backgroundColor: 'rgba(255, 255, 255, 0)'
        });

        // Clear button listener
        clearButton.addEventListener('click', function (event) {
            event.preventDefault();
            signaturePad.clear();
        });

        // Form submit listener
        if (form) {
            form.addEventListener('submit', function (event) {
                if (signaturePad.isEmpty()) {
                    event.preventDefault();
                    alert("Tanda tangan tidak boleh kosong!");
                } else {
                    var dataUrl = signaturePad.toDataURL('image/png');
                    signatureInput.value = dataUrl;
                }
            });
        }
    }
});
