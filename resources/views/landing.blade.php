<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoStaff - Pointage Mobile & Gestion du Temps pour PME</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        accent: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    <!-- Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-3">
                <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="h-12 w-auto object-contain">
            </a>

            <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                <a href="#features" class="hover:text-blue-600 transition">Fonctionnalités</a>
                <a href="#how-it-works" class="hover:text-blue-600 transition">Comment ça marche</a>
                <a href="#install" class="hover:text-blue-600 transition">Installer l'App</a>
                <a href="#contact" class="hover:text-blue-600 transition">Contact</a>
            </nav>

            <a href="{{ route('login') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50 transition">
                Espace Client
            </a>
        </div>
    </header>

    <!-- Hero Section (Bannière Attractive) -->
    <section class="relative overflow-hidden bg-gradient-to-b from-blue-50/60 via-white to-slate-50 pt-16 pb-24 lg:pt-24 lg:pb-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            
            <div class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 mb-6">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                La solution complète de pointage web + mobile pour PME
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight max-w-4xl mx-auto leading-tight sm:leading-none">
                Le pointage intelligent, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-emerald-500">simple et instantané</span> pour vos équipes.
            </h1>

            <p class="mt-6 text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Fini les badges perdus et les feuilles d'émargement papier. Vos collaborateurs badgent en un scan sur leur smartphone (Android & iPhone), même sans réseau.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/app" target="_blank" 
                   class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-emerald-600 hover:from-blue-700 hover:to-emerald-700 text-white font-bold rounded-2xl text-base shadow-xl hover:shadow-2xl transition transform hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="fa-solid fa-mobile-screen-button mr-3 text-xl"></i>
                    Télécharger l'application
                </a>
            </div>

            <!-- Showcase Preview Banner -->
            <div class="mt-12 max-w-4xl mx-auto rounded-3xl bg-slate-900/95 p-3 sm:p-5 shadow-2xl border border-slate-700/50 backdrop-blur-sm">
                <div class="flex items-center justify-between pb-3 px-2 border-b border-slate-800 text-xs text-slate-400">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="ml-2 font-mono text-[11px] text-slate-400 hidden sm:inline">chronostaff.com &mdash; Tableau de bord temps réel</span>
                    </div>
                    <span class="inline-flex items-center text-emerald-400 font-semibold text-[11px]">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping mr-1.5"></span> En direct
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 text-left">
                    <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Salariés Présents</div>
                        <div class="text-2xl font-black text-white mt-1">98.4%</div>
                        <div class="text-[11px] text-emerald-400 mt-0.5"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Ponctualité optimale</div>
                    </div>
                    <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mode Déconnecté</div>
                        <div class="text-2xl font-black text-emerald-400 mt-1">Actif</div>
                        <div class="text-[11px] text-slate-400 mt-0.5"><i class="fa-solid fa-cloud-arrow-up mr-1"></i> Synchro auto en arrière-plan</div>
                    </div>
                    <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rapports & Audit</div>
                        <div class="text-2xl font-black text-blue-400 mt-1">PDF / Word</div>
                        <div class="text-[11px] text-slate-400 mt-0.5"><i class="fa-solid fa-file-shield mr-1"></i> Conforme inspections</div>
                    </div>
                </div>
            </div>

            <!-- Key Feature Badges under Hero -->
            <div class="mt-14 pt-10 border-t border-slate-200/80 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-4xl mx-auto text-left">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-wifi"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">100% Hors-Ligne</div>
                        <div class="text-[11px] text-slate-500">Synchronisation auto</div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Géolocalisation</div>
                        <div class="text-[11px] text-slate-500">Sur site ou à distance</div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Pauses Auto</div>
                        <div class="text-[11px] text-slate-500">Badgeage matin & soir</div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                        <i class="fa-brands fa-apple"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">iOS & Android</div>
                        <div class="text-[11px] text-slate-500">Zéro Mac requis</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Section: Fonctionnalités Clés -->
    <section id="features" class="py-20 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Conçu pour les gérants & les équipes</span>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-2">
                    Toutes les fonctionnalités nécessaires, sans complexité inutile
                </h2>
                <p class="text-slate-500 text-sm mt-3">
                    ChronoStaff offre le parfait équilibre entre contrôle pour la direction et simplicité absolue pour les employés.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Pointage QR Code & Code PIN</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        L'employé ouvre l'application, pointe la caméra sur son badge imprimé ou saisit son PIN à 4 chiffres. Le système détecte automatiquement s'il s'agit d'une arrivée ou d'un départ.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Mode Hors-Ligne Résilient</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Sur un chantier, un parking sous-sol ou en zone blanche : l'employé badge normalement. Les pointages sont sécurisés dans le smartphone et envoyés automatiquement dès que la 4G/WiFi revient.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Tableau "Qui est là ?" en direct</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Le gérant ou le superviseur voit en temps réel qui est présent sur site, qui travaille à distance, avec l'heure d'arrivée exacte et le temps écoulé, actualisé toutes les 30 secondes.
                    </p>
                </div>

                <!-- Card 4 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Déduction de Pause Automatique</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Inutile de faire badger les salariés le midi : le système applique automatiquement vos règles (ex: -30 min si le shift dépasse 6h) et arrondit au quart d'heure pour la paie.
                    </p>
                </div>

                <!-- Card 5 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Badges QR Code Imprimables</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Chaque salarié dispose d'un badge professionnel généré automatiquement avec vos logos, son département, son code PIN et son QR code imprimable en format carte en 1 clic.
                    </p>
                </div>

                <!-- Card 6 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl shadow-md">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Rapports & Export CSV Comptable</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Sélectionnez une période et filtrez par département : obtenez les heures brutes et nettes travaillées par salarié, et téléchargez le fichier CSV prêt pour votre logiciel de paie.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Comment Installer l'Application Mobile (Guide PWA) -->
    <section id="install" class="py-20 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Installation Instantanée</span>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-2">
                    Comment installer l'App sur votre téléphone
                </h2>
                <p class="text-slate-500 text-sm mt-2 max-w-xl mx-auto">
                    Grâce à la technologie Progressive Web App, l'application s'installe directement depuis le navigateur sans passer par les stores d'applications.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Guide iPhone -->
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center space-x-3 text-slate-900 font-bold text-lg">
                        <i class="fa-brands fa-apple text-3xl"></i>
                        <span>Sur iPhone (Safari)</span>
                    </div>
                    <ol class="list-decimal list-inside text-sm text-slate-600 space-y-2.5 pt-2">
                        <li>Ouvrez le lien <strong class="text-blue-600">/app</strong> dans <strong>Safari</strong>.</li>
                        <li>Touchez le bouton de partage <i class="fa-solid fa-arrow-up-from-bracket text-blue-600 mx-1"></i> en bas de l'écran.</li>
                        <li>Faites défiler et sélectionnez <strong>« Sur l'écran d'accueil »</strong>.</li>
                        <li>Cliquez sur <strong>Ajouter</strong> en haut à droite.</li>
                    </ol>
                    <p class="text-xs text-slate-400 pt-2 border-t border-slate-100">
                        L'icône ChronoStaff apparaît sur votre écran d'accueil et s'ouvre en plein écran !
                    </p>
                </div>

                <!-- Guide Android -->
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center space-x-3 text-slate-900 font-bold text-lg">
                        <i class="fa-brands fa-android text-3xl text-emerald-600"></i>
                        <span>Sur Android (Chrome)</span>
                    </div>
                    <ol class="list-decimal list-inside text-sm text-slate-600 space-y-2.5 pt-2">
                        <li>Ouvrez le lien <strong class="text-blue-600">/app</strong> dans <strong>Google Chrome</strong>.</li>
                        <li>Touchez le menu des trois points <i class="fa-solid fa-ellipsis-vertical text-slate-600 mx-1"></i> en haut à droite.</li>
                        <li>Sélectionnez <strong>« Installer l'application »</strong> ou <strong>« Ajouter à l'écran d'accueil »</strong>.</li>
                        <li>Confirmez l'installation.</li>
                    </ol>
                    <p class="text-xs text-slate-400 pt-2 border-t border-slate-100">
                        L'application fonctionne comme une application native, avec accès caméra et GPS.
                    </p>
                </div>
            </div>

            <div class="mt-12 text-center">
                <a href="/app" target="_blank" 
                   class="inline-flex items-center px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-base shadow-lg transition">
                    <i class="fa-solid fa-mobile-screen-button mr-2.5 text-lg"></i>
                    Télécharger l'application
                </a>
            </div>
        </div>
    </section>

    <!-- Section: Contact & Démonstration -->
    <section id="contact" class="py-20 bg-white border-t border-slate-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Une question ? Un projet ?</span>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-2">
                    Contactez-nous pour une démonstration
                </h2>
                <p class="text-slate-500 text-sm mt-2 max-w-lg mx-auto">
                    Nous sommes disponibles pour vous accompagner dans le déploiement de ChronoStaff au sein de votre entreprise.
                </p>
            </div>

            <!-- Contact Buttons: WhatsApp & Email -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-5 pt-4">
                <a href="https://wa.me/22655270101?text=Bonjour,%20je%20souhaite%20des%20informations%20sur%20ChronoStaff" 
                   target="_blank"
                   class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center">
                    <i class="fa-brands fa-whatsapp text-2xl mr-3"></i>
                    Écrivez-nous sur WhatsApp
                </a>

                <a href="mailto:rawelgui02@gmail.com" 
                   class="w-full sm:w-auto px-8 py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center">
                    <i class="fa-solid fa-envelope text-xl mr-3"></i>
                    Contactez-nous par Email
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="h-10 w-auto brightness-0 invert">
            </div>
            <p class="text-xs text-slate-400">
                ChronoStaff &copy; {{ date('Y') }} &mdash; Solution de pointage & gestion du temps pour PME.
            </p>
            <div class="flex space-x-6 text-xs text-slate-400">
                <a href="/app" class="hover:text-white transition">Application Mobile</a>
            </div>
        </div>
    </footer>

</body>
</html>

