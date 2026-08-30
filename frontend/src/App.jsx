import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import NouvelleConsultation from './pages/NouvelleConsultation';
import ListeConsultations from './pages/ListeConsultations';
import DetailsConsultation from './pages/DetailsConsultation';
import Fournisseurs from './pages/Fournisseurs';
import GestionAoo from './pages/GestionAoo';
import GestionMarches from './pages/GestionMarches';
import ListeAoo from './pages/ListeAoo';
import ListeMarche from './pages/ListeMarche';
import ListeEngagements from './pages/ListeEngagements';
import TraitementEngagement from './pages/TraitementEngagement';
import MembresCommission from './pages/MembresCommission';
import GestionBonDeCommande from './pages/GestionBonDeCommande';
import BonCommandePlateforme from './pages/BonCommandePlateforme';
import GestionConvention from './pages/GestionConvention';
import ListeLiquidations from './pages/ListeLiquidations';
import DossierLiquidation from './pages/DossierLiquidation';
import Login from './pages/Login';
import Register from './pages/Register';
import ProtectedRoute from './components/ProtectedRoute';
import DossierCloture from './pages/DossierCloture';
import ListeNotifications from './pages/ListeNotifications';
import NouvelleNotification from './pages/NouvelleNotification';
import DetailsNotification from './pages/DetailsNotification';
function App() {
  const isAuthenticated = !!localStorage.getItem('token');

  return (
    <Router>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/notifications"
          element={
            <ProtectedRoute>
              <ListeNotifications />
            </ProtectedRoute>
          }
        />
        <Route
          path="/notifications/nouvelle"
          element={
            <ProtectedRoute>
              <NouvelleNotification />
            </ProtectedRoute>
          }
        />
        <Route
          path="/notifications/:id"
          element={
            <ProtectedRoute>
              <DetailsNotification />
            </ProtectedRoute>
          }
        />
        <Route
          path="/fournisseurs"
          element={
            <ProtectedRoute>
              <Fournisseurs />
            </ProtectedRoute>
          }
        />
        <Route
          path="/commission-membres"
          element={
            <ProtectedRoute>
              <MembresCommission />
            </ProtectedRoute>
          }
        />
        <Route
          path="/consultations"
          element={
            <ProtectedRoute>
              <ListeConsultations />
            </ProtectedRoute>
          }
        />
        <Route
          path="/consultations/nouvelle"
          element={
            <ProtectedRoute>
              <NouvelleConsultation />
            </ProtectedRoute>
          }
        />
        <Route
          path="/consultations/:id"
          element={
            <ProtectedRoute>
              <DetailsConsultation />
            </ProtectedRoute>
          }
        />
        <Route
          path="/aoos"
          element={
            <ProtectedRoute>
              <ListeAoo />
            </ProtectedRoute>
          }
        />
        <Route
          path="/aoos/:id"
          element={
            <ProtectedRoute>
              <GestionAoo />
            </ProtectedRoute>
          }
        />
        <Route
          path="/bons-commande"
          element={
            <ProtectedRoute>
              <BonCommandePlateforme />
            </ProtectedRoute>
          }
        />
        <Route
          path="/bons-de-commande"
          element={
            <ProtectedRoute>
              <BonCommandePlateforme />
            </ProtectedRoute>
          }
        />
        <Route
          path="/conventions"
          element={
            <ProtectedRoute>
              <GestionConvention />
            </ProtectedRoute>
          }
        />
        <Route
          path="/marches"
          element={
            <ProtectedRoute>
              <ListeMarche />
            </ProtectedRoute>
          }
        />
        <Route
          path="/engagements"
          element={
            <ProtectedRoute>
              <ListeEngagements />
            </ProtectedRoute>
          }
        />
        <Route
          path="/engagements/nouveau"
          element={
            <ProtectedRoute>
              <TraitementEngagement />
            </ProtectedRoute>
          }
        />
        <Route
          path="/engagements/:id"
          element={
            <ProtectedRoute>
              <TraitementEngagement />
            </ProtectedRoute>
          }
        />
        <Route
          path="/liquidations"
          element={
            <ProtectedRoute>
              <ListeLiquidations />
            </ProtectedRoute>
          }
        />
        <Route
          path="/liquidations/marches/:id"
          element={
            <ProtectedRoute>
              <DossierLiquidation />
            </ProtectedRoute>
          }
        />
        <Route
          path="/marches/:id"
          element={
            <ProtectedRoute>
              <GestionMarches />
            </ProtectedRoute>
          }
        />
        <Route
          path="/marches/:id/cloture"
          element={
            <ProtectedRoute>
              <DossierCloture />
            </ProtectedRoute>
          }
        />

        <Route path="/" element={<Navigate to={isAuthenticated ? '/dashboard' : '/login'} replace />} />
      </Routes>
    </Router>
  );
}

export default App;
