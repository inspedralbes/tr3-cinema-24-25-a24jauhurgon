<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bitllets — last24bcn</title>
    <style>
        /* DejaVu Sans for best DomPDF compatibility */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #101922;
            color: #e2e8f0;
            padding: 20px;
        }

        .boarding-pass {
            background: linear-gradient(135deg, #162431 0%, #101922 100%);
            border: 1px solid rgba(19, 127, 236, 0.2);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        /* === HEADER === */
        .bp-header {
            background: rgba(19, 127, 236, 0.05);
            padding: 18px 24px;
            border-bottom: 1px solid rgba(19, 127, 236, 0.1);
            display: table;
            width: 100%;
        }
        .bp-header-left { display: table-cell; vertical-align: middle; width: 55%; }
        .bp-header-right { display: table-cell; vertical-align: middle; text-align: right; width: 45%; }
        .bp-logo { font-size: 24px; font-weight: bold; color: #ffffff; letter-spacing: -0.5px; }
        .bp-logo span { color: #137fec; }
        .bp-tag {
            display: inline-block;
            background: rgba(19, 127, 236, 0.1);
            border: 1px solid rgba(19, 127, 236, 0.2);
            color: #137fec;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 4px;
        }
        .bp-reserva-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; }
        .bp-reserva-code { font-size: 20px; font-weight: bold; color: #137fec; font-family: monospace; letter-spacing: 2px; }

        /* === ROUTE === */
        .bp-route-section {
            padding: 24px;
            display: table;
            width: 100%;
        }
        .bp-route-left { display: table-cell; vertical-align: middle; width: 65%; }
        .bp-route-right { display: table-cell; vertical-align: top; text-align: center; width: 35%; }
        .bp-route-row { display: table; }
        .bp-route-cell { display: table-cell; vertical-align: middle; padding-right: 12px; }
        .bp-iata { font-size: 42px; font-weight: bold; color: #ffffff; letter-spacing: 3px; }
        .bp-city { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: -2px; }
        .bp-arrow { color: #137fec; font-size: 18px; padding: 0 6px; }
        .bp-flight-info { margin-top: 14px; }
        .bp-info-row { display: table; width: 100%; margin-bottom: 6px; }
        .bp-info-cell { display: table-cell; width: 50%; }
        .bp-info-label { font-size: 8px; color: #475569; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; }
        .bp-info-value { font-size: 13px; font-weight: bold; color: #e2e8f0; }

        /* === QR CODE === */
        .bp-qr-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 8px;
            display: inline-block;
        }
        .bp-qr-container img {
            width: 100px;
            height: 100px;
        }
        .bp-qr-text {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        /* === DASHED SEPARATOR === */
        .bp-separator {
            border-top: 2px dashed rgba(19, 127, 236, 0.15);
            margin: 0;
            position: relative;
        }

        /* === PASSENGER + SEAT === */
        .bp-passenger-section {
            padding: 18px 24px;
            display: table;
            width: 100%;
        }
        .bp-pass-left { display: table-cell; vertical-align: middle; width: 60%; }
        .bp-pass-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }
        .bp-pass-label { font-size: 8px; color: #475569; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; }
        .bp-pass-name { font-size: 18px; font-weight: bold; color: #ffffff; margin-top: 2px; }
        .bp-pass-type {
            display: inline-block;
            background: rgba(19, 127, 236, 0.1);
            color: #137fec;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 10px;
            border-radius: 4px;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .bp-seat-box {
            background: rgba(19, 127, 236, 0.05);
            border: 1px solid rgba(19, 127, 236, 0.2);
            border-radius: 10px;
            padding: 10px 20px;
            display: inline-block;
            text-align: center;
        }
        .bp-seat-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .bp-seat-code { font-size: 30px; font-weight: bold; color: #137fec; }

        /* === PRICE BAR === */
        .bp-price-bar {
            background: rgba(19, 127, 236, 0.03);
            border-top: 1px solid rgba(19, 127, 236, 0.1);
            padding: 12px 24px;
            display: table;
            width: 100%;
        }
        .bp-price-left { display: table-cell; vertical-align: middle; width: 60%; }
        .bp-price-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }
        .bp-price-tarifa { font-size: 11px; color: #64748b; }
        .bp-price-value { font-size: 20px; font-weight: bold; color: #137fec; }

        /* === TOTAL === */
        .total-section {
            background: rgba(19, 127, 236, 0.05);
            border: 1px solid rgba(19, 127, 236, 0.2);
            border-radius: 14px;
            padding: 20px 24px;
            display: table;
            width: 100%;
            margin-top: 8px;
        }
        .total-left { display: table-cell; vertical-align: middle; width: 60%; }
        .total-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }
        .total-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; }
        .total-count { font-size: 13px; color: #94a3b8; margin-top: 2px; }
        .total-value { font-size: 32px; font-weight: bold; color: #137fec; }
        .total-euro { font-size: 18px; color: #64748b; font-weight: normal; }

        /* === FOOTER === */
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            font-size: 9px;
            color: #475569;
            letter-spacing: 1px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($bitllets as $index => $bitllet)
    <div class="boarding-pass {{ $index < count($bitllets) - 1 ? 'page-break' : '' }}">
        <!-- Header -->
        <div class="bp-header">
            <div class="bp-header-left">
                <div class="bp-logo">last24<span>bcn</span></div>
                <div class="bp-tag">✈ Boarding Pass</div>
            </div>
            <div class="bp-header-right">
                <div class="bp-reserva-label">Codi de Reserva</div>
                <div class="bp-reserva-code">#{{ $compra->id }}</div>
            </div>
        </div>

        <!-- Route + QR -->
        <div class="bp-route-section">
            <div class="bp-route-left">
                <div class="bp-route-row">
                    <div class="bp-route-cell">
                        <div class="bp-iata">{{ $vol->origenIata }}</div>
                        <div class="bp-city">Barcelona</div>
                    </div>
                    <div class="bp-route-cell">
                        <span class="bp-arrow">——✈——▶</span>
                    </div>
                    <div class="bp-route-cell">
                        <div class="bp-iata">{{ $vol->destiIata }}</div>
                        <div class="bp-city">{{ $vol->destiIata }}</div>
                    </div>
                </div>
                <div class="bp-flight-info">
                    <div class="bp-info-row">
                        <div class="bp-info-cell">
                            <div class="bp-info-label">Data i Hora</div>
                            <div class="bp-info-value">{{ \Carbon\Carbon::parse($vol->dataHoraSortida)->format('d/m/Y  H:i') }}h</div>
                        </div>
                        <div class="bp-info-cell">
                            <div class="bp-info-label">Núm. Vol</div>
                            <div class="bp-info-value">{{ $vol->externalId }}</div>
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-cell">
                            <div class="bp-info-label">Avió</div>
                            <div class="bp-info-value">{{ $vol->modelAvio->nomModel ?? '-' }}</div>
                        </div>
                        <div class="bp-info-cell">
                            <div class="bp-info-label">Terminal</div>
                            <div class="bp-info-value">T1</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bp-route-right">
                <div class="bp-qr-container">
                    <img src="{{ $qrCodes[$index] }}" alt="QR" />
                </div>
                <div class="bp-qr-text">Escaneja per verificar</div>
            </div>
        </div>

        <!-- Separator -->
        <div class="bp-separator"></div>

        <!-- Passenger + Seat -->
        <div class="bp-passenger-section">
            <div class="bp-pass-left">
                <div class="bp-pass-label">Passatger</div>
                <div class="bp-pass-name">{{ $bitllet->nomPassatger }}</div>
                <div class="bp-pass-type">{{ $bitllet->tipus }}</div>
            </div>
            <div class="bp-pass-right">
                <div class="bp-seat-box">
                    <div class="bp-seat-label">Seient</div>
                    <div class="bp-seat-code">{{ $bitllet->fila }}{{ chr(64 + $bitllet->columna) }}</div>
                </div>
            </div>
        </div>

        <!-- Price bar -->
        <div class="bp-price-bar">
            <div class="bp-price-left">
                <div class="bp-price-tarifa">Tarifa {{ $bitllet->tipus }}</div>
            </div>
            <div class="bp-price-right">
                <div class="bp-price-value">{{ number_format($bitllet->preu, 2) }} €</div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Total -->
    <div class="total-section">
        <div class="total-left">
            <div class="total-label">Total Compra</div>
            <div class="total-count">{{ count($bitllets) }} bitllet{{ count($bitllets) > 1 ? 's' : '' }}</div>
        </div>
        <div class="total-right">
            <div class="total-value">{{ number_format($compra->total, 2) }} <span class="total-euro">€</span></div>
        </div>
    </div>

    <div class="footer">
        last24bcn — Real-time Booking Engine • Gràcies per volar amb nosaltres
    </div>
</body>
</html>
