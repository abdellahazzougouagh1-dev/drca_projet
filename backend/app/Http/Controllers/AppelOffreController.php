namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AppelOffreController extends Controller
{
    public function genererAvisPdf(Request $request, $id)
    {
        // 1. Récupérer l'appel d'offres depuis la BDD (ou les données du formulaire)
        // Vous pouvez aussi utiliser l'IA/Copilot ici si vous devez structurer du texte complexe
        $donnees = [
            'etablissement' => 'Office National du Conseil Agricole',
            'direction_regionale' => 'Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra',
            'type_ao' => 'NATIONAL',
            'numero_ao' => '06/2026/DRCA-RSK',
            'type_seance' => 'Séance publique',
            'date_ouverture' => '10/06/2026',
            'heure_ouverture' => '10',
            'lieu_ouverture' => 'salle des réunions au siège de la Direction Régionale...',
            'objet_ao' => 'l\'organisation des journées de formation au profit des agriculteurs...',
            'url_portail' => 'www.marchespublics.gov.ma',
            'estimations' => [
                ['description' => 'Lot 1', 'montant_lettres' => 'Six cent cinquante-huit mille vingt', 'montant_chiffres' => '658 020,00'],
                ['description' => 'Lot 2', 'montant_lettres' => 'Cent quatre-vingt-dix-sept mille neuf cent vingt', 'montant_chiffres' => '197 920,00'],
            ],
            'cautionnements' => [
                ['description' => 'Lot 1', 'montant_lettres' => 'Dix mille', 'montant_chiffres' => '10 000,00'],
                ['description' => 'Lot 2', 'montant_lettres' => 'Trois mille', 'montant_chiffres' => '3 000,00'],
            ],
            'articles_decret' => '30 à 34 et 135',
            'numero_decret' => '2-22-431',
        ];

        // 2. Charger la vue Blade avec les données
        $pdf = Pdf::loadView('pdf.avis_publication', compact('donnees'));

        // 3. Configurer le papier A4
        $pdf->setPaper('a4', 'portrait');

        // 4. Télécharger ou afficher le PDF dans le navigateur
        return $pdf->stream('Avis_Appel_Offres_' . $donnees['numero_ao'] . '.pdf');
    }
}