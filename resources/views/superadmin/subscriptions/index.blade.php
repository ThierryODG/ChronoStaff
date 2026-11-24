<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnements - ChronoStaff</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-full flex flex-col font-sans text-slate-800">
    <header class="bg-slate-900 text-white border-b border-slate-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-5 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold">Abonnements</h1>
                <p class="text-xs text-slate-400">Espace propriétaire ChronoStaff</p>
            </div>
            <a href="{{ route('superadmin.companies.index') }}" class="px-3 py-2 text-sm text-slate-200 border border-slate-700 hover:bg-slate-800">
                Entreprises
            </a>
        </div>
    </header>

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <section class="bg-white border border-slate-200 p-8 sm:p-12 text-center">
            <div class="w-12 h-12 mx-auto mb-4 flex items-center justify-center bg-slate-100 text-slate-500">
                <i class="fa-solid fa-credit-card text-xl"></i>
            </div>
            <h2 class="text-xl font-semibold text-slate-900">Gestion des abonnements</h2>
            <p class="mt-2 text-sm text-slate-500">Cette section sera activée ultérieurement. Aucun abonnement n'est géré pour le moment.</p>
        </section>
    </main>
</body>
</html>
