# 📖 Guide pour tester vos routes d’API avec Postman ou HTTPie

Ce guide est destiné aux **débutants** qui n’ont jamais travaillé avec des API. Ici, on va apprendre à tester vos routes backend Symfony avec **Postman** ou **HTTPie Desktop**, deux outils graphiques similaires.

> 💡 Remarque : les routes `/api/companies` sont **juste des exemples** pour vous montrer comment tester des API.
> Vous devrez par la suite remplacer ces routes par celles que **vous allez créer**.

---

## 1. Comprendre les routes

Dans notre exemple, nous avons deux routes principales pour gérer des entreprises (`Company`) :

| Méthode | URL              | Description                                                   |
| ------- | ---------------- | ------------------------------------------------------------- |
| `GET`   | `/api/companies` | Récupérer toutes les entreprises                              |
| `POST`  | `/api/companies` | Créer une nouvelle entreprise (envoie un JSON avec les infos) |

---

## 2. Tester avec Postman ou HTTPie Desktop

Les deux outils fonctionnent de manière très similaire : tu choisis la **méthode HTTP**, l’**URL**, et éventuellement un **body JSON**.

### Étape A — GET `/api/companies`

1. Ouvrir Postman ou HTTPie Desktop.
2. Créer une nouvelle requête.
3. Choisir **GET** comme méthode.
4. Entrer l’URL complète : `http://localhost:8000/api/companies` (ou l’URL de votre serveur Symfony).
5. Cliquer sur **Send** ou **Exécuter**.
6. La réponse en JSON affichera la liste des entreprises.

### Étape B — POST `/api/companies`

1. Créer une nouvelle requête.
2. Choisir **POST** comme méthode.
3. Entrer l’URL : `http://localhost:8000/api/companies`.
4. Aller dans l’onglet **Body** > **raw** > **JSON**.
5. Écrire un JSON avec les infos de l’entreprise, par exemple :

```json
{
    "name": "OpenAI",
    "description": "Entreprise d’IA",
    "website": "https://openai.com",
    "location": "San Francisco",
    "size": "MEDIUM"
}
```

Cliquer sur Send ou Exécuter.

Vous verrez la réponse JSON avec l’entreprise créée et son id.

## 3. Points importants à retenir

-   **Méthodes HTTP** :
    -   `GET` = récupérer des données
    -   `POST` = créer des données
    -   `PUT` ou `PATCH` = modifier des données
    -   `DELETE` = supprimer des données
-   **JSON** : format standard pour envoyer/recevoir des données.
-   Postman et HTTPie Desktop sont **visuels**, faciles à prendre en main pour tester vos API.
-   Vous pouvez choisir l’outil que vous préférez : interface graphique et simplicité avant tout.
