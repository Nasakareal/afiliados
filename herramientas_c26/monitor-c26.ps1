[CmdletBinding()]
param(
    [string]$Puerto = 'COM6',
    [switch]$SolicitarFlujo,
    [int]$DuracionSegundos = 0
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$etapas = @{
    1 = 'Etapa 1 (movimiento, 23 s observados)'
    2 = 'Etapa 2 (movimiento, 40 s observados)'
    3 = 'Etapa 3 (transicion, 3 s observados)'
    4 = 'Etapa 4 (movimiento, 26 s observados)'
    5 = 'Etapa 5 (transicion, 3 s observados)'
}

$puertoSerie = New-Object System.IO.Ports.SerialPort(
    $Puerto,
    2400,
    [System.IO.Ports.Parity]::None,
    8,
    [System.IO.Ports.StopBits]::One
)
$puertoSerie.Handshake = [System.IO.Ports.Handshake]::None
$puertoSerie.DtrEnable = $false
$puertoSerie.RtsEnable = $false
$puertoSerie.Encoding = [System.Text.Encoding]::ASCII
$puertoSerie.ReadTimeout = 250

$buffer = ''
$ultimo = ''
$inicio = Get-Date

try {
    $puertoSerie.Open()

    if ($SolicitarFlujo) {
        # ENQ (0x05): solicitud de consulta; no contiene datos de programacion.
        $consulta = [byte[]](0x05)
        $puertoSerie.Write($consulta, 0, $consulta.Length)
    }

    Write-Host "Monitor C26 conectado a $Puerto - 2400 baudios, 8N1" -ForegroundColor Green
    Write-Host 'Para salir, presiona Ctrl+C.'

    while (($DuracionSegundos -eq 0) -or (((Get-Date) - $inicio).TotalSeconds -lt $DuracionSegundos)) {
        $texto = $puertoSerie.ReadExisting()
        if ($texto.Length -gt 0) {
            $buffer += $texto
        }

        $coincidencia = [regex]::Match(
            $buffer,
            '<([0-9A-Fa-f]{6})><([0-9A-Fa-f]{3})><([0-9A-Fa-f]{4})><([0-9A-Fa-f]{2})>'
        )

        while ($coincidencia.Success) {
            $trama = $coincidencia.Value.ToUpperInvariant()
            $salidas = $coincidencia.Groups[1].Value.ToUpperInvariant()
            $codigo = $coincidencia.Groups[2].Value.ToUpperInvariant()
            $tiempoEtapa = $coincidencia.Groups[3].Value.ToUpperInvariant()
            $plan = $coincidencia.Groups[4].Value.ToUpperInvariant()
            $segundos = [Convert]::ToInt32($tiempoEtapa.Substring(0, 2), 16)
            $etapa = [Convert]::ToInt32($tiempoEtapa.Substring(2, 2), 16)

            if ($trama -ne $ultimo) {
                Clear-Host
                Write-Host 'MONITOR C26 - COMUNICACION ACTIVA' -ForegroundColor Green
                Write-Host "Puerto:       $Puerto (2400, 8N1)"
                Write-Host "Etapa:        $etapa - $($etapas[$etapa])" -ForegroundColor Cyan
                Write-Host "Restante:     $segundos segundos" -ForegroundColor Yellow
                Write-Host "Salidas:      $salidas"
                Write-Host "Codigo fijo:  $codigo"
                Write-Host "Plan/ciclo:   $plan (significado por confirmar)"
                Write-Host "Trama:        $trama"
                Write-Host ''
                Write-Host 'Lectura solamente: este monitor no modifica la programacion.'
                $ultimo = $trama
            }

            $fin = $coincidencia.Index + $coincidencia.Length
            $buffer = $buffer.Substring($fin)
            $coincidencia = [regex]::Match(
                $buffer,
                '<([0-9A-Fa-f]{6})><([0-9A-Fa-f]{3})><([0-9A-Fa-f]{4})><([0-9A-Fa-f]{2})>'
            )
        }

        if ($buffer.Length -gt 1024) {
            $buffer = $buffer.Substring($buffer.Length - 256)
        }

        Start-Sleep -Milliseconds 50
    }
}
finally {
    if ($puertoSerie.IsOpen) {
        $puertoSerie.Close()
    }
    $puertoSerie.Dispose()
}
