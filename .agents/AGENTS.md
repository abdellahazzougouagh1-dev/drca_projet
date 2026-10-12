# Règles de fidélité visuelle pour la génération de PDF

Règle stricte et absolue pour la création de modèles PDF :

1. **Reproduction exacte des modèles** : Chaque modèle fourni par l'utilisateur (image ou PDF) est la référence absolue. L'agent doit reproduire son apparence et sa structure fidèlement, sans "moderniser" ni réorganiser le document.
2. **Analyse minutieuse avant de coder** :
   - Format de page, marges, orientation.
   - En-tête : taille et position exactes des logos, espacements, titres.
   - Typographie : police, taille, gras, italique, soulignement, interligne.
   - Géométrie : positions horizontales et verticales, largeur des blocs, hauteurs des lignes, espacements entre les sections, indentations.
3. **Données dynamiques** : Le texte fixe reste intact. Les variables (ex: `{{ numero_ao }}`) doivent s'insérer exactement à la place des données variables sans casser l'alignement ni le style.
4. **Tableaux** : Reproduire scrupuleusement le nombre de colonnes, bordures, hauteurs, alignements.
5. **Logos et images (STRICT)** : 
   - Rechercher les fichiers originaux existants dans le projet (.png, .jpg, .svg).
   - Ne jamais recréer un logo en HTML, CSS, ou le remplacer par du texte.
   - Si introuvable, signaler immédiatement "Logo original introuvable dans le projet" et demander le fichier.
   - Conserver le ratio original (`width: ...; height: auto;`).
   - Positionner et dimensionner exactement selon le modèle (ne pas les centrer par défaut ni utiliser le flux normal si ça modifie la position).
   - Utiliser des chemins absolus (ou `public_path()`) pour s'assurer que DomPDF les lise correctement.
6. **Génération PDF** : Toujours utiliser Modèle visuel -> HTML/CSS -> Injection de données -> PDF via DomPDF. (Ne jamais utiliser PHPWord).
7. **Méthodologie de Test** :
   - Analyser le modèle.
   - Créer un template HTML/CSS spécifique.
   - Utiliser des données de test (dummy data).
   - Générer le PDF (créer une route de test dédiée).
   - Comparer visuellement avec l'image originale.
   - Corriger les différences.
   - Seulement après validation visuelle par l'utilisateur, connecter aux données de la base.
8. **Séparation des modèles** : Ne jamais supposer que le Modèle 2 partage la même géométrie que le Modèle 1. Chaque modèle nécessite sa propre analyse, son propre template et son propre CSS (pour ne rien casser).
9. **Priorité absolue** : Fidélité au modèle officiel > Rapidité ou simplicité du code. Le PDF généré DOIT ressembler à l'original. Toujours expliquer brièvement les éléments visuels remarqués avant de coder.
