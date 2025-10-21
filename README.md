# FindJob
JobBoard React/Symfony. Plateforme de mise en relation entre candidats et recruteurs avec backend dynamique.
# 🧭 FindJob

**FindJob** est une plateforme de type *jobboard* développée dans le cadre d’un projet pédagogique à Epitech Marseille. Elle permet de mettre en relation des candidats et des recruteurs via une interface moderne, dynamique et responsive.

---

## 🚀 Objectifs du projet

- Concevoir une application web complète avec une architecture frontend/backend claire
- Permettre aux recruteurs de publier des offres d’emploi
- Permettre aux candidats de consulter, filtrer et postuler aux offres
- Gérer les utilisateurs, les rôles et les interactions via une API sécurisée

---

## 🛠️ Stack technique

- **Frontend** : [React](https://reactjs.org/) + CSS
- **Backend** : [Symfony](https://symfony.com/) + API Platform
- **Base de données** : MySQL
- **Authentification** : JWT
- **Autres outils** : Docker, Git, Postman

---


## 📦 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/A-Crapanzano/FindJob.git
cd FindJob

cd backend
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony server:start

cd frontend
npm install
npm run dev

*******

Ce projet est à but pédagogique. Toute réutilisation doit mentionner l’auteur original.
