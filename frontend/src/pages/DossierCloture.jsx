import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, FileText, CheckCircle2, ShieldCheck, 
  Award, Calendar, Upload, Plus, AlertCircle, Save,
  FileDown
} from 'lucide-react';
import api from '../api/axios';

const DossierCloture = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [formData, setFormData] = useState({
    type_cautionnement: 'définitif',
    reference_caution: '',
    date_caution: '',
    organisme_caution: '',
    montant_caution: 0,
    date_validite_caution: '',
    motif_mainlevee: 'Exécution totale et satisfaisante',
    conditions_mainlevee: 'Réception définitive prononcée sans réserves.',
    observations_mainlevee: '',
    signataire_mainlevee: '',
    date_signature_mainlevee: '',
    
    date_reception_provisoire: '',
    date_reception_definitive: '',
    qualite_execution: 'Satisfaisante',
    respect_delais: true,
    reserves_emises: false,
    reserves_levees: false,
    signataire_certificat: ''
  });

  useEffect(() => {
    fetchDossier();
  }, [id]);

  const fetchDossier = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/marches/${id}/cloture`);
      setData(res.data);
      if (res.data.cloture) {
        setFormData({ ...formData, ...res.data.cloture });
      }
    } catch (err) {
      setError('Erreur lors du chargement du dossier de clôture.');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? checked : value
    });
  };

  const handleSave = async (e) => {
    e.preventDefault();
    try {
      await api.post(`/marches/${id}/cloture`, formData);
      alert('Données de clôture enregistrées avec succès.');
      fetchDossier();
    } catch (err) {
      alert(err.response?.data?.error || 'Erreur lors de l\'enregistrement.');
    }
  };

  const handleValidate = async () => {
    if (!window.confirm("Êtes-vous sûr de vouloir clôturer définitivement ce marché ? Cette action est irréversible et verrouillera la génération des documents.")) return;
    
    try {
      await api.post(`/marches/${id}/cloture/valider`);
      fetchDossier();
    } catch (err) {
      alert(err.response?.data?.error || 'Erreur lors de la validation.');
    }
  };

  const generateDoc = async (type) => {
    try {
      const res = await api.get(`/marches/${id}/cloture/documents/${type}`, { responseType: 'blob' });
      
      const mimeType = res.data.type || res.headers['content-type'] || '';
      const extension = (mimeType.includes('pdf')) ? 'pdf' : 'docx';
      
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${type}_${data.marche.num_marche}.${extension}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      alert(err.response?.data?.error || 'Erreur lors de la génération du document. (Avez-vous ajouté le fichier modèle .docx ?)');
    }
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Chargement du dossier...</div>;
  if (error) return <div className="p-8 text-center text-red-500">{error}</div>;
  if (!data) return null;

  const { marche, workflow } = data;
  const isLocked = marche.statut === 'cloture_validee';

  return (
    <div className="min-h-screen bg-slate-50 font-sans p-6">
      <main className="flex-1 max-w-7xl mx-auto space-y-6">
        
        {/* Header */}
        <div className="flex items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
          <button onClick={() => navigate('/dashboard')} className="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <ArrowLeft size={24} className="text-slate-600" />
          </button>
          <div>
            <h1 className="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
              Clôture du Marché <span className="text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg text-lg border border-indigo-100">{marche.num_marche}</span>
            </h1>
            <p className="text-sm text-slate-500 mt-1 line-clamp-1">{marche.objet_marche}</p>
          </div>
          {isLocked && (
            <div className="ml-auto flex items-center gap-2 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl border border-emerald-200 font-bold">
              <CheckCircle2 size={20} /> Marché Clôturé Définitivement
            </div>
          )}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          {/* Left Column: Forms */}
          <div className="lg:col-span-2 space-y-6">
            <form onSubmit={handleSave} className="space-y-6">
              
              {/* Cautionnement / Mainlevée */}
              <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-2">
                  <ShieldCheck className="text-indigo-600" size={20}/> Données de Cautionnement & Mainlevée
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Type Cautionnement</label>
                    <input disabled={isLocked} type="text" name="type_cautionnement" value={formData.type_cautionnement} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Organisme Financier</label>
                    <input disabled={isLocked} type="text" name="organisme_caution" value={formData.organisme_caution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Montant Caution (DH)</label>
                    <input disabled={isLocked} type="number" name="montant_caution" value={formData.montant_caution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Référence Caution</label>
                    <input disabled={isLocked} type="text" name="reference_caution" value={formData.reference_caution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Date Caution</label>
                    <input disabled={isLocked} type="date" name="date_caution" value={formData.date_caution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Date Validité (Échéance)</label>
                    <input disabled={isLocked} type="date" name="date_validite_caution" value={formData.date_validite_caution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                </div>

                <div className="mt-4 pt-4 border-t border-slate-100">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Motif de Mainlevée</label>
                      <input disabled={isLocked} type="text" name="motif_mainlevee" value={formData.motif_mainlevee} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Date de signature de Mainlevée</label>
                      <input disabled={isLocked} type="date" name="date_signature_mainlevee" value={formData.date_signature_mainlevee} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Signataire (Mainlevée)</label>
                      <input disabled={isLocked} type="text" name="signataire_mainlevee" value={formData.signataire_mainlevee} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                    </div>
                  </div>
                </div>
              </div>

              {/* Exécution / Certificat */}
              <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-2">
                  <Award className="text-indigo-600" size={20}/> Évaluation de l'Exécution (Certificat de Référence)
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Date Réception Provisoire</label>
                    <input disabled={isLocked} type="date" name="date_reception_provisoire" value={formData.date_reception_provisoire} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Date Réception Définitive</label>
                    <input disabled={isLocked} type="date" name="date_reception_definitive" value={formData.date_reception_definitive} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Qualité de l'exécution</label>
                    <select disabled={isLocked} name="qualite_execution" value={formData.qualite_execution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                      <option value="Satisfaisante">Satisfaisante</option>
                      <option value="Moyenne">Moyenne</option>
                      <option value="Insuffisante">Insuffisante</option>
                    </select>
                  </div>
                  
                  <div className="md:col-span-3 flex gap-6 pt-2">
                    <label className="flex items-center gap-2 text-sm font-bold text-slate-700">
                      <input disabled={isLocked} type="checkbox" name="respect_delais" checked={formData.respect_delais} onChange={handleInputChange} className="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                      Respect des délais
                    </label>
                    <label className="flex items-center gap-2 text-sm font-bold text-slate-700">
                      <input disabled={isLocked} type="checkbox" name="reserves_emises" checked={formData.reserves_emises} onChange={handleInputChange} className="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                      Réserves émises à la réception
                    </label>
                    {formData.reserves_emises && (
                      <label className="flex items-center gap-2 text-sm font-bold text-amber-700">
                        <input disabled={isLocked} type="checkbox" name="reserves_levees" checked={formData.reserves_levees} onChange={handleInputChange} className="rounded text-amber-600 focus:ring-amber-500 w-4 h-4" />
                        Réserves levées
                      </label>
                    )}
                  </div>
                  
                  <div className="md:col-span-3 pt-2">
                    <label className="block text-xs font-bold text-slate-700 mb-1">Signataire (Certificat)</label>
                    <input disabled={isLocked} type="text" name="signataire_certificat" value={formData.signataire_certificat} onChange={handleInputChange} className="w-1/2 px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" />
                  </div>
                </div>
              </div>

              {!isLocked && (
                <div className="flex justify-end gap-3">
                  <button type="submit" className="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-colors shadow-md flex items-center gap-2">
                    <Save size={20} /> Sauvegarder les données
                  </button>
                </div>
              )}
            </form>
          </div>

          {/* Right Column: Actions & Documents */}
          <div className="space-y-6">
            
            <div className="bg-slate-900 text-white p-6 rounded-2xl shadow-lg border border-slate-800">
              <h2 className="text-lg font-bold mb-4 flex items-center gap-2">
                <FileText className="text-indigo-400" size={20}/> Documents de Clôture
              </h2>
              <p className="text-slate-400 text-sm mb-6">
                Générez les documents officiels marquant la fin du marché. La génération nécessite la sauvegarde préalable des données ci-contre.
              </p>
              
              <div className="space-y-3">
                <button onClick={() => generateDoc('mainlevee')} className="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-bold flex items-center justify-between transition-colors">
                  <span className="flex items-center gap-2"><FileDown size={18} className="text-emerald-400"/> Mainlevée</span>
                  <span className="text-xs bg-slate-700 px-2 py-1 rounded text-slate-300">Générer</span>
                </button>
                <button onClick={() => generateDoc('certificat_reference')} className="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-bold flex items-center justify-between transition-colors">
                  <span className="flex items-center gap-2"><FileDown size={18} className="text-amber-400"/> Certificat de Référence</span>
                  <span className="text-xs bg-slate-700 px-2 py-1 rounded text-slate-300">Générer</span>
                </button>
              </div>

              {!isLocked && (
                <div className="mt-8 pt-6 border-t border-slate-800">
                  <button onClick={handleValidate} className="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-colors shadow-lg shadow-red-900/20">
                    <CheckCircle2 size={18} /> Clôturer Définitivement
                  </button>
                  <p className="text-xs text-slate-400 text-center mt-3">Cette action verrouillera le dossier et empêchera toute modification ultérieure.</p>
                </div>
              )}
            </div>

          </div>

        </div>
      </main>
    </div>
  );
};

export default DossierCloture;
