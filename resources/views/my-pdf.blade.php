<!DOCTYPE html>
<html>

<head>
    <title>Laravel 10 How to Add QR Code in PDF Using DomPDF</title>
</head>

<body>

    <div>
        <h1>Laravel PDF with QR Code Example</h1>

        <p>
            Phasellus nonummy? Porta venenatis magnam, tortor? Vivamus elementum hac magna mi ut magni praesent fugit
            per! Aute molestias sapiente recusandae adipiscing imperdiet cupidatat temporibus per nibh duis etiam? Illo
            malesuada! Exercitation cum recusandae iaculis vel, proident faucibus deleniti minus diamlorem, quisque
            suscipit? Nunc rerum, ligula felis ullamcorper posuere, anim, vehicula sollicitudin iusto maxime perferendis
            curae? Voluptate metus excepturi ultricies cum! Viverra vero libero ratione interdum rerum. Nemo debitis
            commodi expedita, esse rutrum? Faucibus nulla eos, wisi ab adipiscing? Ligula, hendrerit, porttitor laoreet
            purus molestiae, sodales augue eius perspiciatis, interdum parturient? Error odit? Vel, ullamcorper netus
            occaecati odio! Eos minima expedita.
        </p>

        <img src="data:image/png;base64,{{ $qrcode }}" alt="QR Code">
        <img src="{{ $qrcode }}" alt="QR Code">
        {{}}

    </div>

</body>

</html>