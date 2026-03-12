<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #137fec 0%, #0b5db3 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            letter-spacing: -1px;
        }
        .content {
            padding: 40px;
        }
        .info-box {
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        .flight-route {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #64748b;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LAST24<span style="font-weight: normal; opacity: 0.8;">BCN</span></h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 5px;">Confirmació de Reserva</p>
        </div>
        <div class="content">
            <h2 style="color: #0f172a; margin-top: 0;">Hola!</h2>
            <p>Gràcies per confiar en <strong>last24bcn</strong> per al teu viatge d'última hora. La teva reserva s'ha completat correctament.</p>
            
            <div class="info-box">
                <div style="text-align: center;">
                    <h2 style="margin: 0; font-size: 20px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Reserva</h2>
                    <div style="font-size: 20px; color: #137fec; font-weight: bold; margin-bottom: 20px;">#{{ $compra->id }}</div>
                    
                    <div class="flight-route">
                        {{ $compra->volIntern->origenIata }} 
                        <span style="color: #137fec; font-size: 18px;">&rarr;</span> 
                        {{ $compra->volIntern->destiIata }}
                    </div>
                    
                    <p style="margin: 5px 0; color: #475569;">
                        Sortida: {{ \Carbon\Carbon::parse($compra->volIntern->dataHoraSortida)->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            <p>T'hem adjuntat els bitllets en format <strong>PDF</strong> a aquest correu electrònic. Hauràs de presentar-los a l'aeroport (ja sigui impresos o al mòbil).</p>
            
            <p style="font-size: 14px; color: #475569;">Detall de la compra: {{ number_format($compra->total, 2, ',', '.') }} €</p>
            
            <div style="text-align: center; margin-top: 40px;">
                <p style="margin-bottom: 10px;">Bon viatge!</p>
                <div style="font-weight: bold; color: #0f172a;">L'equip de last24bcn</div>
            </div>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} last24bcn. Tots els drets reservats.</p>
            Aquest es un correu automàtic, si us plau no responguis.
        </div>
    </div>
</body>
</html>
