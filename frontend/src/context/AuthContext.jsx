import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../api/axios';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [currentUser, setCurrentUser] = useState(() => {
    try {
      const savedUser = localStorage.getItem('user');
      return savedUser ? JSON.parse(savedUser) : null;
    } catch {
      return null;
    }
  });

  const [token, setToken] = useState(() => localStorage.getItem('token') || null);

  // Global Login Modal State
  const [loginModalConfig, setLoginModalConfig] = useState({
    isOpen: false,
    title: 'Connexion requise',
    subtitle: 'Veuillez vous connecter pour continuer.',
    requiredRole: null,
    onSuccess: null,
  });

  // Keep axios authorization header in sync
  useEffect(() => {
    if (token) {
      api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
      delete api.defaults.headers.common.Authorization;
    }
  }, [token]);

  // Sync user state changes with localStorage
  const updateCurrentUser = (userData) => {
    if (userData) {
      localStorage.setItem('user', JSON.stringify(userData));
      setCurrentUser(userData);
    } else {
      localStorage.removeItem('user');
      setCurrentUser(null);
    }
  };

  const login = async (email, password, requiredRole = null) => {
    const response = await api.post('/login', { email, password });
    const { token: receivedToken, user } = response.data;

    if (requiredRole && user.role !== requiredRole) {
      throw new Error(
        requiredRole === 'directeur'
          ? "Accès refusé : Seul le compte Directeur est autorisé."
          : `Accès refusé : Rôle '${requiredRole}' requis pour cette section.`
      );
    }

    localStorage.setItem('token', receivedToken);
    localStorage.setItem('user', JSON.stringify(user));
    setToken(receivedToken);
    setCurrentUser(user);
    api.defaults.headers.common.Authorization = `Bearer ${receivedToken}`;

    return { token: receivedToken, user };
  };

  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setToken(null);
    setCurrentUser(null);
    delete api.defaults.headers.common.Authorization;
    window.location.href = '/login';
  };

  const openLoginModal = ({ title, subtitle, requiredRole, onSuccess } = {}) => {
    setLoginModalConfig({
      isOpen: true,
      title: title || 'Connexion requise',
      subtitle: subtitle || 'Veuillez vous identifier avec vos identifiants.',
      requiredRole: requiredRole || null,
      onSuccess: onSuccess || null,
    });
  };

  const closeLoginModal = () => {
    setLoginModalConfig(prev => ({ ...prev, isOpen: false, onSuccess: null }));
  };

  return (
    <AuthContext.Provider
      value={{
        currentUser,
        setCurrentUser: updateCurrentUser,
        token,
        login,
        logout,
        loginModalConfig,
        openLoginModal,
        closeLoginModal,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;
