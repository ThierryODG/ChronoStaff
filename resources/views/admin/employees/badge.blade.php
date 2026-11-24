<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge Employé - {{ $employee->name }} - ChronoStaff</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            body { background: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-badge { box-shadow: none !important; border: 1px dashed #94a3b8 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4 flex flex-col items-center justify-center font-sans">

    <!-- Action Bar (Hidden on print) -->
    <div class="no-print mb-8 flex items-center space-x-4">
        <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold shadow hover:bg-blue-700 transition flex items-center">
            <i class="fa-solid fa-print mr-2"></i> Imprimer le Badge
        </button>
        <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-xl font-medium hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour
        </a>
    </div>

    <!-- Official Employee Badge Card (Format Standard Badge ~ 85x54mm proportion) -->
    <div class="print-badge w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden text-center relative flex flex-col">
        <!-- Top Colored Header with Brand -->
        <div class="bg-gradient-to-r from-blue-700 to-blue-900 py-6 px-6 text-white relative">
            <div class="flex items-center justify-between">
                <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="h-8 w-auto brightness-0 invert">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/20 font-mono">
                    BADGE SALARIÉ
                </span>
            </div>
        </div>

        <div class="p-6 flex-1 flex flex-col items-center">
            <!-- Avatar initial -->
            <div class="w-16 h-16 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-800 flex items-center justify-center text-xl font-bold -mt-12 shadow-md">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>

            <!-- Employee Info -->
            <h2 class="text-xl font-bold text-slate-900 mt-3">{{ $employee->name }}</h2>
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mt-0.5">
                {{ $employee->departments->pluck('name')->join(', ') ?: 'Général' }}
            </p>

            <!-- QR Code Container (Multi-fallback for 100% guarantee) -->
            <div class="mt-4 p-3 bg-white border-2 border-slate-200 rounded-2xl shadow-inner inline-block">
                <!-- SVG/Image QR Code - Loads instantly without JS dependency -->
                <img id="badge-qrcode" 
                     src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=svg&data={{ urlencode($employee->qr_token) }}" 
                     alt="QR Code {{ $employee->name }}"
                     class="w-44 h-44 object-contain mx-auto"
                     onerror="this.onerror=null; this.src='https://quickchart.io/qr?size=200&text={{ urlencode($employee->qr_token) }}';">
            </div>

            <p class="text-[11px] text-slate-400 mt-2 font-mono">Token: {{ $employee->qr_token }}</p>

            <!-- PIN Code fallback box -->
            <div class="mt-4 w-full bg-slate-50 border border-slate-200 rounded-xl p-3 flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Code PIN de secours :</span>
                <span class="font-mono font-bold text-slate-900 text-base tracking-widest">{{ $employee->pin_code }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 border-t border-slate-100 py-2.5 px-4 text-center text-[10px] text-slate-400">
            {{ $employee->company->name ?? 'ChronoStaff' }} &mdash; ID Employé #{{ $employee->id }}
        </div>
    </div>
</body>
</html>
