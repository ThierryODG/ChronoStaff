Je vais résumer ChronoStaff à partir de ses routes, modèles et écrans, sans consulter le fichier `.env` ni afficher de configuration secrète.

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\routes\\api.php"
}

Find files matching `back/app/Models/*.php`

Find files matching `back/app/Http/Controllers/**/*.php`

Find files matching `back/resources/views/**/*.blade.php`

Find files matching `back/database/migrations/*.php`

Find files matching `back/public/mobile/**/*`

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\app\\Models\\User.php"
}

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\app\\Models\\Company.php"
}

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\app\\Models\\TimeLog.php"
}

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\app\\Models\\Kiosk.php"
}

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\public\\mobile\\manifest.json"
}

Read 
Completed with input: {
  "path": "Y:\\PROJET\\APP\\ChronoStaff\\back\\routes\\web.php"
}

## ChronoStaff — description du projet

**ChronoStaff** est une application de gestion du temps de travail destinée aux entreprises. Elle permet aux employés de pointer leurs entrées et sorties depuis une application mobile installable (PWA), et aux responsables de suivre les présences, gérer les équipes et consulter les rapports d’heures.

Les employés peuvent s’identifier par code PIN ou QR code. L’application prend en charge la synchronisation des pointages effectués hors ligne et peut enregistrer la position de l’employé pour vérifier sa présence sur site. Les responsables peuvent gérer les employés et les départements, corriger des pointages, traiter les justifications et exporter des rapports. Une interface distincte permet à l’administrateur de la plateforme de gérer les entreprises.

**Fonctionnalités principales**
- Pointage mobile par PIN ou QR code, avec mode hors ligne.
- Gestion des employés, départements et entreprises.
- Tableau de bord des présences et historique des pointages.
- Rapports périodiques et exports CSV, PDF et Word.
- Justifications d’absence ou de pointage et ajustements manuels.
- Paramètres d’entreprise : horaires, pauses, arrondis, géolocalisation et bornes de pointage.
- Notifications push et rappels configurables.

**Technologies :** le serveur utilise Laravel 13 et PHP 8.3, avec une API authentifiée et une application mobile web installable. Les fonctionnalités sont visibles dans les `routes API`, les `routes web` et le `manifeste de l’application mobile`.
