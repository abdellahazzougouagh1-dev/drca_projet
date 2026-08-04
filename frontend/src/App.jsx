import React from 'react';
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
import MembresCommission from './pages/MembresCommission';
import Login from './pages/Login';
import Register from './pages/Register';
import ProtectedRoute from './components/ProtectedRoute';

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
          path="/marches"
          element={
            <ProtectedRoute>
              <ListeMarche />
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

        <Route path="/" element={<Navigate to={isAuthenticated ? '/dashboard' : '/login'} replace />} />
      </Routes>
    </Router>
  );
}

export default App;
